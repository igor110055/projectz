# from colorama import *
from binance.client import Client
from binance import ThreadedWebsocketManager
import argparse
from datetime import datetime

api_key = 'hQH2h98cmqZqbvdefR8FkWVWKB4dggL0hymzS62tNfNjMHbp19ifSq3ArOr11mXR'
api_secret = 'gyNHJVSm1qo2g1SOnpPDUi6uDzQiGVOTQbUkzTB4BenNixCzWzJEjcCoqlqfWAdb'

class StopLoss:
    def __init__(self):
        parser = argparse.ArgumentParser()
        parser.add_argument("symbol", help="symbol for the establish socket connection")
        parser.add_argument("high_price", help="best (highest price) during the session")
        parser.add_argument("quantity", help="quantity amounth to sell or buy")
        args = parser.parse_args()

        if args.symbol:
            self.symbol = args.symbol
        if args.high_price:
            self.high_price = args.high_price
        if args.quantity:
            self.qty = args.quantity

        self.ratio = 0.98
        self.stop_loss = float(self.high_price) * self.ratio
        self.client = Client(api_key,api_secret)
        self.now = datetime.now().strftime('%x %X')
        self.initial_price = 0

    def initial_price(self, price):
        if self.initial_price == 0:
            self.initial_price = price
            return True

    def percentage(self, new, old):
        return "{:.2f}".format(((float(new) - float(old)) / float(old)) * 100)

    def update_high_price(self, price):
        self.now = datetime.now().strftime('%x %Y')
        if float(price) > float(self.high_price):
            self.high_price = float(price)
            self.stop_loss = float(price) * self.ratio
            print(f"{self.now} - {self.symbol} - Updated - Best Price: {price}, Stop Loss: {self.stop_loss}")

    def check_stop_loss(self, price):
        return float(price) * 1.001 < self.stop_loss

    def set_price(self, msg):
        price = msg['k']['c']
        if self.initial_price == 0:
            self.initial_price = price
        progress = self.percentage(price, self.initial_price)
        self.update_high_price(price)   # update high price
        print(f"{self.now} - {self.symbol} - Current Price : {price} . Stop Price :"+
            f" {self.stop_loss} . Position : {self.qty} . Unrealized PNL : {progress} %")
        return price

    def handle_socket_message(self, msg):
        price = self.set_price(msg)
        if self.check_stop_loss(price):
            print("{self.now} - {self.symbol} - Triggered Stop Loss")
            order = self.client.order_limit_sell(
                symbol=self.symbol, quantity=self.qty, price=price)
            print(order)

    def websocket_connection(self):
        twm = ThreadedWebsocketManager(api_key=api_key, api_secret=api_secret)
        twm.start()
        twm.start_kline_socket(callback=self.handle_socket_message, symbol=self.symbol)
        twm.join()
        print("{self.now} - Websocket connection established for {self.symbol}")

    def run(self):
        print(f"{self.now} - Trailing: {self.symbol}, Initial Stop Loss: {self.stop_loss}")
        self.websocket_connection()

stop_loss = StopLoss()
stop_loss.run()
