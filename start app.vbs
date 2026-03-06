Set WshShell = CreateObject("WScript.Shell")
Dim fso: Set fso = CreateObject("Scripting.FileSystemObject")

' Kunin ang kasalukuyang directory ng script
strPath = fso.GetParentFolderName(WScript.ScriptFullName)

' Path ng PHP (base sa XAMPP default path mo)
phpExe = "C:\xampp\php\php.exe"

' Kung wala sa XAMPP path, subukan ang generic "php" command
If Not fso.FileExists(phpExe) Then
    phpExe = "php"
End If

' 1. Patakbuhin ang PHP Server ng SILENT (0 = tago ang CMD window)
WshShell.Run """" & phpExe & """ -S localhost:8000 -t """ & strPath & """", 0, False

' 2. Maghintay ng sandali para mag-start ang server
WScript.Sleep 1500

' 3. Buksan ang Chrome sa APP MODE (Walang address bar, mukhang real app)
' Gagamit din tayo ng sariling profile folder para hindi magulo ang main Chrome mo
chromeCmd = "cmd /c start chrome --app=http://localhost:8000 --user-data-dir=""" & strPath & "\.chrome-profile"""
WshShell.Run chromeCmd, 0, False
