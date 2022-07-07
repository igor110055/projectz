# Python program to print
# colored text and background
from termcolor import colored, cprint
 
def magenta_bold(text):
    cprint(colored(text, 'magenta', attrs=['bold']))

def cyan_bold(text):
    cprint(colored(text, 'cyan', attrs=['bold']))
 
# magenta_bold('Hello, World!')
# cyan_bold('Hello, World!')

cyan_on_magenta = lambda x: cprint(x, 'cyan', 'on_magenta')
magenta_on_cyan = lambda x: cprint(x, 'magenta', 'on_cyan')

# cyan_on_magenta('Hello, World!')
# magenta_on_cyan('Hello, World!')
