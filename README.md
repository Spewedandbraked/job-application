перед установкой убедитесь, что порт 80 и 5432 свободны
локальная установка приложения - docker compose -f compose.dev.yaml up --build -d
прод установка (не протестиована, может вообще не работать) - docker compose -f compose.prod.yaml up --build -d 

после установки ресурс доступен по http://localhost/