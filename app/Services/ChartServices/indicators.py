from bfeeder import BFeeder
import pandas_ta as ta
import argparse

parser = argparse.ArgumentParser(description='Technical Indicators')
parser.add_argument('--token', type=str, help='token')
parser.add_argument('--indicator', type=str, help='Indicator')
parser.add_argument('--interval', type=str, help='interval')
parser.add_argument('--periods', type=int, help='Periods')
args = parser.parse_args()

def main():
    if args.token:
        df =BFeeder().get(args.token,args.interval,'1 days ago UTC')
    else:
        df = BFeeder().get(args.token,args.interval,'1 days ago UTC')

    if args.indicator == 'bbands':
        bands = ta.bbands(df.Close, args.periods)
    else:
        bands = "nothing"
    return bands

main()
