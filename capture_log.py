
try:
    with open('storage/logs/laravel.log', 'r', encoding='utf-8') as f:
        lines = f.readlines()
    
    with open('last_error.log', 'w', encoding='utf-8') as out:
        out.write(''.join(lines[-100:]))
except Exception as e:
    with open('last_error.log', 'w') as out:
        out.write(str(e))
