import argparse
from binance.helpers import round_step_size

def main(*args, **kwargs):
    parser = argparse.ArgumentParser()

    parser.add_argument("--quantity", "-q", help="quantity to round",type=float)
    parser.add_argument("--step", "-s", help="stepSize from exchange info", type=float)

    args = parser.parse_args()

    if args.quantity:
        quant = args.quantity
    else:
        raise Exception("No quantity provided. Please provide a quantity with --quantity or -q flag")

    if args.step:
        step_size = args.step
    else:
        raise Exception("No stepSize provided. Please provide a stepSize with --step or -s flag")

    rounded = round_step_size(quant, float(step_size))
    print(rounded)
    return rounded

if __name__ == "__main__":
    main()
