from flask import Flask
from flask import request
from matplotlib import scale
import mplfinance as mpf
from datetime import datetime
from bfeeder import BFeeder
from flask import jsonify
import pandas_ta as ta
# from mysql_engine import query
# from stockstats import StockDataFrame
# from binance.client import Client

"""
export FLASK_APP=app
export FLASK_ENV=development
flask run
"""
app = Flask(__name__)

@app.route('/')
def home():
    return 'Hello, World!'

# @app.route('/create_chart/symbol/<symbol>/resistances/<resistances>/supports/<supports>',methods=['POST'])
    #     # df = draw(symbol,df)
    #     # return {'status':'ok','file':df}
    #     pass

@app.route('/chart/<symbol>')
def sup_res(symbol):
    df = draw(symbol)
    return {'status':'ok','file':df }

def get_data(symbol):
    df = BFeeder().get(f"{symbol.upper()}","1h","6 days ago UTC")
    return df
    # df = query(f'SELECT open as Open, High as High, Low as Low, price as Close, volume as Volume, timestamp as Date FROM hourly_prices WHERE SYMBOL ="{symbol}"')
        # df['Date'] = pd.to_datetime(df['Date'])
        # df = df.set_index('datetime')
        #
        # df_list = df.values.tolist()
        # JSONP_data = jsonify(df)
        # return JSONP_data

# def resistances(df):
    #     df = df.reset_index()
    #     resistances = {}
    #     for i in range(len(df.index)):
    #         if i > 1 and i < len(df) - 1:
    #             if df.iloc[i]['High'] > df.iloc[i-1]['High'] and df.iloc[i]['High'] > df.iloc[i+1]['High']:
    #                 resistances[i] = df.iloc[i]['High']
    #             if df.iloc[i]['Low'] < df.iloc[i-1]['Low'] and df.iloc[i]['Low'] < df.iloc[i+1]['Low']:
    #                 resistances[i] = df.iloc[i]['Low']
    #     return resistances

#support(df,46,3,2)
def sup_res(df):
    values = []
    n1=3
    n2=2
    for row in range(n1, len(df)-n2):
        if support(df, row, n1, n2):
            values.append(df.Low[row])
    for row in range(n1, len(df)-n2):
        if resistance(df, row, n1, n2):
            values.append(df.Low[row])
    values.sort()

    return  values

def support(df1, l, n1, n2): #n1 n2 before and after candle l
    for i in range(l-n1+1, l+1):
        if(df1.Low[i]>df1.Low[i-1]):
            return 0
    for i in range(l+1,l+n2+1):
        if(df1.Low[i]<df1.Low[i-1]):
            return 0
    return 1

#resistance(df, 30, 3, 5)
def resistance(df1, l, n1, n2): #n1 n2 before and after candle l
    for i in range(l-n1+1, l+1):
        if(df1.High[i]<df1.High[i-1]):
            return 0
    for i in range(l+1,l+n2+1):
        if(df1.High[i]>df1.High[i-1]):
            return 0
    return 1

def draw(symbol):
    df = get_data(symbol)
    extralines = sup_res(df)
    #tlines = sup_res(df)['tlines']
    mc = mpf.make_marketcolors(up='g',down='r',edge="w" ,wick="w", volume="inherit")
    s  = mpf.make_mpf_style(base_mpf_style='mike',marketcolors=mc,y_on_right=False,facecolor="#060a1f",
                            edgecolor="black", gridcolor='#272842',figcolor="#060a1f",gridstyle='-')

    name=f"{symbol}-{datetime.now().strftime('%Y-%m-%d')}.png"
    mpf.plot(df, type='candle',style=s, figratio=(23,11), figscale=1.4, title=symbol,tight_layout=True, #volume=True ,
              hlines=dict(hlines=extralines, linestyle='-.'),
              savefig=f"../../../public/charts/{name}")
              #tlines=dict(tlines=tlines, tline_use="High"),
    # mpf.plot(df, type='candle',style=s, figratio=(12,9), figscale=1, title=symbol,tight_layout=True, #volume=True ,
    #           hlines=dict(hlines=,colors='r',linestyle='-.',linewidths=(2)),
    #           savefig=f"../../../public/charts/{name}")
             #  hlines=dict(hlines=extralines,colors=['g','r'],linestyle='-.',linewidths=(2)),savefig=f"../../../public/charts/{name}" )
    return "http://localhost:8000/charts/"+name

@app.route('/technicals/bbands/<symbol>/<timeframe>/<since>')
def bbands(symbol,timeframe,since):
    print(symbol,timeframe,since)
    # df = BFeeder().get(symbol, timeframe, since)
    # bbands = ta.bbands(df, timeperiod=20, nbdevup=2, nbdevdn=2, matype=0)
    # return bbands
