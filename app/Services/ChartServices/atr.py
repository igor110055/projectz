import argparse
from binance.client import Client
import pandas as pd
import pandas_ta as ta

def main(*args, **kwargs):
    parser = argparse.ArgumentParser()

    parser.add_argument("--token", "-t", help="symbol for the stock")

    args = parser.parse_args()

    if args.token:
        token = args.token
    else:
        raise Exception("No token provided. Please provide a token with --token or -t flag")

    klines = Client().get_historical_klines(token,'15m','6 months ago UTC')
    df = pd.DataFrame(klines, columns=["Datetime","Open", "High", "Low", "Close", "Volume","i","i","i","i","i","i"], dtype=float)
    return ta.atr(df['High'], df['Low'], df['Close'], timeperiod=14)

main()
