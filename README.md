# Compilar una vez (producción)
npx @tailwindcss/cli -i ./resources/css/app.css -o ./public/css/tailwind.css --minify

# Modo watch (desarrollo activo con hot-reload del CSS)
npx @tailwindcss/cli -i ./resources/css/app.css -o ./public/css/tailwind.css --watch
