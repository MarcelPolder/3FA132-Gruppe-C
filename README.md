# 3FA132-Gruppe-C
BSInfo Projekt Repo

## Backend starten

Um das Backend der Hausverwaltung zu starten, folge bitte den untenstehenden Schritten:

1. Stelle sicher, dass du Maven auf deinem System installiert hast. Falls nicht, kannst du es von der offiziellen [Maven-Website](https://maven.apache.org/download.cgi) herunterladen und installieren.

2. Navigiere zu `Backend/hausverwaltung`

3. Öffne ein Terminal oder eine Befehlszeile und führe den folgenden Befehl aus, um das Backend zu bauen und zu starten:

```bash
mvn clean install
java -jar target/hausverwaltung-backend.jar
```
Dieser Befehl kompiliert den Code, erstellt das JAR-Archiv und startet dann den Server.

Nach erfolgreicher Ausführung sollten Meldungen erscheinen, die darauf hinweisen, dass der Server gestartet wurde. Standardmäßig wird der Server auf http://localhost:8080 lauschen.

## Frontend starten

Um den Next.js-Server des Frontends zu starten, befolge bitte die folgenden Schritte:

1. Stelle sicher, dass Node.js auf deinem System installiert ist.

2. Navigiere nach `Frontend/next-server`

3. Öffne ein Terminal oder eine Befehlszeile und führe die folgenden Befehle aus:

```bash
yarn install
yarn next dev
```

Der Entwicklungs-Server startet und das Frontend wird auf http://localhost:3000 verfügbar sein.

Öffne deinen Webbrowser und besuche http://localhost:3000, um auf das Frontend der Hausverwaltung zuzugreifen.

### Zugangsdaten
Die Zugangsdaten für die Hausverwaltung lauten wie folgt:
`marcel.polder`:`1234`