PS -> cd C:\(subpath)\Inventario-Uniguajira-Laravel12
PS -> Remove-Item -Recurse -Force "storage/app/public/seeders" -ErrorAction SilentlyContinue
PS -> New-Item -ItemType SymbolicLink -Path "storage/app/public/seeders" -Target "C:\Proyectos\Inventario-Uniguajira-Laravel12\storage\app\seeders"


    Directorio: C:\(subpath)\Inventario-Uniguajira-Laravel12\storage\app\public

Mode                 LastWriteTime         Length Name
----                 -------------         ------ ----
d----l     14/11/2025  11:55 p. m.                seeders


PS C:\(subpath)\Inventario-Uniguajira-Laravel12> Get-ChildItem storage/app/public -Force


    Directorio: C:\(subpath)\Inventario-Uniguajira-Laravel12\storage\app\public


Mode                 LastWriteTime         Length Name
----                 -------------         ------ ----
d----l     14/11/2025  11:55 p. m.                seeders
------      4/11/2025  11:25 a. m.             14 .gitignore
