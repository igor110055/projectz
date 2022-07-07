from binance.client import Client
import pandas as pd
import numpy as np
import datetime
import mplfinance as mpf


def df_direct_api(symbol, interval = "1h", start = "1 week ago UTC"):
    client = Client()
    tickers = client.get_historical_klines(symbol, interval, start)
    columns = ['Date','Open', 'High', 'Low', 'Close', 'Volume', 'CloseTime', 'QuoteAssetVolume', 'TradeTime', 'TakerBuyBaseAssetVolume', 'TakerBuyQuoteAssetVolume', 'Ignore']
    df = pd.DataFrame(tickers, columns=columns)
    return df

def plot_direct_api(symbol, interval = "1h", start = "1 week ago UTC"):
    df = df_direct_api(symbol, interval, start)
    
    df['Open'] = df['Open'].apply(np.float64)
    df['High'] = df['High'].apply(np.float64)
    df['Low'] = df['Low'].apply(np.float64)
    df['Close'] = df['Close'].apply(np.float64)
    df['Volume'] = df['Volume'].apply(np.float64)
    
    df['Date'] = [datetime.datetime.fromtimestamp(item/1000) for item in df['Date']]
    df.set_index('Date', inplace=True)
    mc = mpf.make_marketcolors(up='g',down='r',edge="w" ,wick="w", volume="inherit")
    s  = mpf.make_mpf_style(base_mpf_style='mike',marketcolors=mc,
                            y_on_right=False,facecolor="#060a1f", edgecolor="black",
                            gridcolor='#272842',figcolor="#060a1f",gridstyle='-')
    mpf.plot(df,type='candle',style=s, figratio=(21,11), title=symbol,tight_layout=True,)
    
