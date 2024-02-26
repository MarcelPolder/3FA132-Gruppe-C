package dev.hv.console;

import org.jdbi.v3.sqlobject.statement.SqlUpdate;

public class StartHV {
    public static void main(String[] args) {
        System.out.println("Hello, this is the HV console client!");
        System.out.println("Arguments:");
        for (String arg : args) {
            System.out.println(arg);
        	}
        
        if (args.length > 0 && args[0].equals("-h")) {
            printHelp();
        } else if (args.length > 0 && args[0].equals("--delete")) {
        	//delete all
        } else { 
        	System.out.println("Error resolving Arguments!");
        }
    }
    public static void printHelp() {
    	System.out.printf("!javaversion%s%n", System.getProperty("java.version"));
    	System.out.println("Help");
    	System.out.println("Here is a list of all Arguments:");
    	//list all args
    	System.out.println("-h | zeigt einen Hilfetext mit allen Parametern, ... an und die Versionsnummer der Laufzeitumgebung.");
    	System.out.println("--delete | löscht alle Tabellen und setzt die PK-Zähler, falls vorhanden, zurück.");
    	System.out.println("export ... <tablename> | Es wird ein Export von Daten (Tabelle) durchgeführt");
    	System.out.println("-o, --output=<fileout> | Name der Ausgabedatei");
    	System.out.println("import ... <tablename> | Es wird ein Import von Daten (Tabelle) durchgeführt");
    	System.out.println("-i, --input=<filein> | Name der Eingabedatei");
    	System.out.println("-c | Es wird ein CSV-Format verwendet");
    	System.out.println("-j | Es wird ein JSON-Format verwendet");
    	System.out.println("-x | Es wird ein XML-Format verwendet");
    	System.out.println("-t | Es wird ein Text-Tabellenformat verwendet (nur export)");
    }
    
    }	