PS C:\Users\khafi> cd C:\Users\khafi\Projects\Inventario-Uniguajira-Laravel12
PS C:\Users\khafi\Projects\Inventario-Uniguajira-Laravel12> Remove-Item -Recurse -Force "storage/app/public/seeders" -ErrorAction SilentlyContinue
PS C:\Users\khafi\Projects\Inventario-Uniguajira-Laravel12> New-Item -ItemType SymbolicLink `
>>  -Path "storage/app/public/seeders" `
>>  -Target "C:\Users\khafi\Projects\Inventario-Uniguajira-Laravel12\storage\app\seeders"


    Directorio: C:\Users\khafi\Projects\Inventario-Uniguajira-Laravel12\storage\app\public


Mode                 LastWriteTime         Length Name
----                 -------------         ------ ----
d----l     14/11/2025  11:55 p. m.                seeders


PS C:\Users\khafi\Projects\Inventario-Uniguajira-Laravel12> Get-ChildItem storage/app/public -Force


    Directorio: C:\Users\khafi\Projects\Inventario-Uniguajira-Laravel12\storage\app\public


Mode                 LastWriteTime         Length Name
----                 -------------         ------ ----
d-----     14/11/2025  11:19 p. m.                assets
d----l     14/11/2025  11:55 p. m.                seeders
------      4/11/2025  11:25 a. m.             14 .gitignore
