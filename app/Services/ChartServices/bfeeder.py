from binance.client import Client
import pandas as pd

class BFeeder:
    def __init__(self):
        self.client = Client()
    
    def get(self,token = "BTCUSDT", 
                timeframe = "1h", 
                since = "1 month ago UTC"):
        # got klines from python-binance library
        klines = self.client.get_historical_klines(token,timeframe,since) 
        columns=['Date','Open','High','Low','Close','Volume','i','i','i','i','i','i']
        df = pd.DataFrame(klines, columns = columns, dtype=float)
        df = df.loc[:,:'Volume'] 
        df['Date'] = pd.to_datetime(df['Date'], unit="ms")
        df.set_index('Date', inplace=True) 
        return df

    def read(self, name):
        try:
            df = pd.read_csv(name)
            df['Date'] = pd.to_datetime(df['Date'])
            df.set_index('Date', inplace=True)
            return df
        except print(0):
            pass
        