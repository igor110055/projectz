import mplfinance as mpf
import pandas as pd
from mysql_engine import query
from stockstats import StockDataFrame

df = query(f'SELECT symbol,open,high,low,price as close,volume,timestamp,count as amount FROM hourly_prices WHERE SYMBOL = "BTCUSDT" ORDER BY "timestamp" LIMIT 72')
df.timestamp = pd.to_datetime(df.timestamp)
df = df.set_index('timestamp')
stock = StockDataFrame.retype(df)
stock["boll"]
stock["rsi_14"]
# volume delta against previous day
stock['volume_delta']

# open delta against next 2 day
stock['open_2_d']

# open price change (in percent) between today and the day before yesterday
# 'r' stands for rate.
stock['open_-2_r']

# CR indicator, including 5, 10, 20 days moving average
stock['cr']
stock['cr-ma1']
stock['cr-ma2']
stock['cr-ma3']

# volume max of three days ago, yesterday and two days later
stock['volume_-3,2,-1_max']

# volume min between 3 days ago and tomorrow
stock['volume_-3~1_min']

# KDJ, default to 9 days
stock['kdjk']
stock['kdjd']
stock['kdjj']

# 2 days simple moving average on open price
stock['open_2_sma']

# MACD
stock['macd']
# MACD signal line
stock['macds']
# MACD histogram
stock['macdh']

# bolling, including upper band and lower band
stock['boll']
stock['boll_ub']
stock['boll_lb']

# close price less than 10.0 in 5 days count
stock['close_10.0_le_5_c']

# CR MA2 cross up CR MA1 in 20 days count
stock['cr-ma2_xu_cr-ma1_20_c']

# count forward(future) where close price is larger than 10
stock['close_10.0_ge_5_fc']

# 6 days RSI
stock['rsi_6']
# 12 days RSI
stock['rsi_14']

# 10 days WR
stock['wr_10']
# 6 days WR
stock['wr_6']

# CCI, default to 14 days
stock['cci']
# 20 days CCI
stock['cci_20']

# TR (true range)
stock['tr']
# ATR (Average True Range)
stock['atr']

# DMA, difference of 10 and 50 moving average
stock['dma']

# DMI
# +DI, default to 14 days
stock['pdi']
# -DI, default to 14 days
stock['mdi']
# DX, default to 14 days of +DI and -DI
stock['dx']
# ADX, 6 days SMA of DX, same as stock['dx_6_ema']
stock['adx']
# ADXR, 6 days SMA of ADX, same as stock['adx_6_ema']
stock['adxr']

# TRIX, default to 12 days
stock['trix']
# TRIX based on the close price for a window of 3
stock['close_3_trix']
# MATRIX is the simple moving average of TRIX
stock['trix_9_sma']
# TEMA, another implementation for triple ema
stock['tema']
# TEMA based on the close price for a window of 2
stock['close_2_tema']

# VR, default to 26 days
stock['vr']
# MAVR is the simple moving average of VR
stock['vr_6_sma']

# print(stock)
# print(df.info())
apdict = [
        mpf.make_addplot(df['boll_lb'], color="b"),
        mpf.make_addplot(df['boll'],color="r"),
        mpf.make_addplot(df['boll_ub'], color="b"),
        mpf.make_addplot(df['rsi_14'], color="r", panel=2)
]
print(stock['rsi_14'].head())
# print(stock['rsi_14'])
#mpf.plot(stock, type='candle',style="binance", figratio=(22,9), title="BTGUSDT",tight_layout=True, volume=True,
#         hlines=dict(hlines=[62580,65000],colors=['g','r'],linestyle='-.',linewidths=(2,2) ) )
# mpf.plot(stock, type='candle',style="binance", figratio=(30,18), title="BTCUSDT",tight_layout=True, volume=True, addplot=apdict)
# mpf.plot(stock, type='candle',style="binance", figratio=(30,18),    title="GSXUSDT",tight_layout=True, volume=True, addplot=apdict, savefig='img.png' )
# print(df)
