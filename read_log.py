
try:
    with open('storage/logs/laravel.log', 'r', encoding='utf-8') as f:
        lines = f.readlines()
        print(''.join(lines[-40:]))
except Exception as e:
    print(e)
