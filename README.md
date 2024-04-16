## 1FA132 - Gruppe C - Hausverwaltung

Dieses Projekt, entwickelt von Schülern der Berufsschule für Informationstechnik in München, ist ein Abschlussprojekt, das aus zwei Microservices besteht: einer PHP-Webseite und einem Java-REST-Server. Die Webseite bietet eine intuitive Benutzeroberfläche für die Verwaltung von Hausressourcen, während der REST-Server eine robuste Backend-Infrastruktur bereitstellt. Das Projekt demonstriert die Fähigkeiten der Schüler im Bereich der Webentwicklung und verteilten Systeme.

### Voraussetzungen

- Docker muss auf dem System installiert sein. [Docker Installation Guide](https://docs.docker.com/get-docker/)
- Git sollte ebenfalls installiert sein, um das Repository zu klonen. [Git Installation Guide](https://git-scm.com/book/en/v2/Getting-Started-Installing-Git)

### Anleitung

1. **Repository klonen**: Klonen Sie dieses Repository auf Ihre lokale Maschine.

```bash
	git clone https://github.com/MarcelPolder/3FA132-Gruppe-C.git
```

2. **Docker Compose ausführen**: Wechseln Sie in das Verzeichnis des geklonten Repositorys und führen Sie Docker Compose aus.
```bash
	cd 3FA132-Gruppe-C
	docker-compose up
```
3. **Warten Sie auf den Abschluss**: Docker Compose wird die Container für die PHP-Webseite und den Java-REST-Server erstellen und starten. Bitte warten Sie, bis der Vorgang abgeschlossen ist.

4. **Zugriff auf die Anwendung**: Sobald die Container gestartet sind, können Sie auf die Anwendung über die folgenden URLs zugreifen:

   - Webseiten-Frontend (PHP): http://localhost:3000
   - REST-Server (Java): http://localhost:8080

### Anmerkungen

- Die PHP-Webseite und der Java-REST-Server werden in separaten Containern ausgeführt und über Docker Compose miteinander verbunden.
- Änderungen am Quellcode können direkt im geklonten Repository vorgenommen werden. Nachdem Sie Änderungen vorgenommen haben, können Sie einfach `docker-compose up` erneut ausführen, um die Anwendung mit den neuen Änderungen neu zu starten.

### Mitwirkende
- Marcel Polder
- Moritz Kirchermeier
- Moritz Krug
- Oliver Fuchs
