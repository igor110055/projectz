import numpy as np
import mysql.connector as db
import pandas as pd


def query(query):
    try:
        #conn = db.connect(host="213.136.82.221", database="projectX", username="projectX", password="^6Cm1co7", use_pure=True)
        conn = db.connect(host="localhost", database="projectX", username="mysqluser", password="", use_pure=True)
        df = pd.read_sql(query, conn)
        df['Open'] = df['Open'].apply(np.float64)
        df['High'] = df['High'].apply(np.float64)
        df['Low'] = df['Low'].apply(np.float64)
        df['Close'] = df['Close'].apply(np.float64)
        df['Volume'] = df['Volume'].apply(np.float64)
        return df
    except Exception as e:
        print(str(e))
    finally:
        conn.close()

