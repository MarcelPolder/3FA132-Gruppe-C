package dev.hv.console;

import java.io.File;
import java.io.FileWriter;
import java.io.IOException;
import java.util.List;

import javax.management.openmbean.InvalidOpenTypeException;

import org.jdbi.v3.core.Jdbi;
import org.jdbi.v3.core.result.ResultProducers.ResultSetCreator;
import org.jdbi.v3.sqlobject.SqlObjectPlugin;

import com.google.gson.Gson;
import com.fasterxml.jackson.core.JsonProcessingException;
import com.fasterxml.jackson.dataformat.xml.XmlMapper;

import org.codehaus.plexus.util.xml.pull.XmlSerializer;
import org.jdbi.v3.core.Handle;

import dev.hv.db.dao.ICustomerDAO;
import dev.hv.db.dao.IReadingDAO;
import dev.hv.db.dao.IUserDAO;
import dev.hv.db.init.IDb;
import dev.hv.db.model.DCustomer;
import dev.hv.db.model.DReading;
import dev.hv.db.model.DUser;

public class StartHV {

	private static Jdbi connection = IDb.getInstance().getJdbi();

	public static void main(String[] args) {
		connection.installPlugin(new SqlObjectPlugin());

		try {
			System.out.println("Hello, this is the HV console client!");

			if (args.length == 0) {
				System.out.println("There were no arguments given.");
				return;
			}

			String methodArg = args[0].toLowerCase().trim();
			System.out.println("Running argument: " + methodArg);

			if (methodArg == "-h" || methodArg == "--help") {
				printHelp();
				return;
			} else if (methodArg == "-d" || methodArg == "--delete") {
				System.out.println("Deleting all tables");
				delete(DeleteOptions.All);
				System.out.println("Done deleting tables");
				return;
			} else if (methodArg.equals("-e") || methodArg.equals("--export")) {
				if (args.length < 2 || args[1] == null || args[1].isBlank()) {
					System.out.println("There was no table name given. Please enter a table to export.");
					return;
				}

				if (args.length < 3 || args[2] == null || args[2].isBlank()) {
					System.out.println("There was no file format given. Please enter a file format to export to.");
					return;
				}

				if (args.length < 4 || args[3] == null || args[3].isBlank()) {
					System.out.println("There was no output path given. Please enter an output path to export to.");
					return;
				}

				exportTable(args[1], args[2], args[3]);
			} else if (methodArg.equals("-i") || methodArg.equals("--import")) {
				if (args.length < 2 || args[1] == null || args[1].isBlank()) {
					System.out.println(
							"There was no input path given. Please enter the path to the file you want to import.");
					return;
				}

				if (args.length < 3 || args[2] == null || args[2].isBlank()) {
					System.out.println(
							"There was no file format given. Please enter the file format of the file you want to import.");
					return;
				}

				importFromFile(args[1], args[2]);

			} else {
				System.out.println("Arguments:");
				for (String arg : args) {
					System.out.println(arg);
				}
			}
		} catch (Exception ex) {
			System.out.println(ex.getMessage());
			return;
		}
	}

	public static void printHelp() {
		System.out.println("Here is a list of all Arguments:");
		// list all args
		System.out.println("-h | zeigt einen Hilfetext mit allen Parametern an");
		System.out.println("-d / --delete | löscht alle Tabellen und setzt die PK-Zähler, falls vorhanden, zurück.");
		System.out.println(
				"-e / --export <tablename> -<fileformat> -\"output\" | Es wird ein Export von Daten (Tabelle) durchgeführt");
		System.out.println(
				"-i / --import -\"fileIn\" -<fileformat> | Es wird ein Import von Daten (Tabelle) durchgeführt");
		System.out.println();
		System.out.println("File Formats available: ");
		System.out.println("-c/-csv | Es wird ein CSV-Format verwendet");
		System.out.println("-j/-json | Es wird ein JSON-Format verwendet");
		System.out.println("-x/-xml | Es wird ein XML-Format verwendet");
		System.out.println("-t/-text | Es wird ein Text-Tabellenformat verwendet (nur export)");
	}

	private static void delete(DeleteOptions options) {
		Handle handle = connection.open();

		switch (options) {
		case Users: {
			// delete all Users

			var handler = handle.attach(IUserDAO.class);
			var Users = handler.getAll();

			for (DUser dUser : Users) {
				handler.delete(dUser);
			}
			return;
		}
		case Customers: {
			var handler2 = handle.attach(ICustomerDAO.class);
			var Customers = handler2.getAll();

			for (DCustomer dCustomer : Customers) {
				handler2.delete(dCustomer);
			}
		}
		case Readings: {
			var handler3 = handle.attach(IReadingDAO.class);
			var Readings = handler3.getAll();

			for (DReading dReading : Readings) {
				handler3.delete(dReading);
			}
		}
		case All: {
			delete(DeleteOptions.Users);
			delete(DeleteOptions.Customers);
			delete(DeleteOptions.Readings);
		}
		default:
			throw new IllegalArgumentException("Unexpected value: " + options);
		}
	}

	// ------------------------ Export Functionality
	// ------------------------------------------

	private static <T> String export(ExportFormate format, List<T> objects) throws JsonProcessingException {
		String output = "";

		switch (format) {
		case csv: {
			return output;
		}
		case json: {
			output = new Gson().toJson(objects);
			return output;
		}
		case xml: {
			XmlMapper xmlMapper = new XmlMapper();
			try {
				output = xmlMapper.writeValueAsString(objects);
			} catch (JsonProcessingException e) {
				output = "";
			}
			return output;
		}
		case text: {
			return output;
		}
		default:
			throw new IllegalArgumentException("Unexpected value: " + format);
		}

	}

	private static void exportTable(String tablename, String format, String outPath) throws Exception {
		Handle handle = connection.open();
		ExportFormate formating = ConvertExportFormat(format);

		String output = "";

		if (tablename.toLowerCase().equals("user") || tablename.toLowerCase().equals("users")) {
			final IUserDAO dao = handle.attach(IUserDAO.class);
			var Users = dao.getAll();
			output = export(formating, Users);
		} else if (tablename.toLowerCase() == "readings") {
			var handler3 = handle.attach(IReadingDAO.class);
			var Readings = handler3.getAll();
			output = export(formating, Readings);
		} else if (tablename.toLowerCase() == "customers") {
			var handler2 = handle.attach(ICustomerDAO.class);
			var Customers = handler2.getAll();
			output = export(formating, Customers);
		} else {
			throw new Exception("Could not find table " + tablename);
		}

		saveToFile(output, outPath);
	}

	private static void saveToFile(String output, String outPath) {
		System.out.println("Saving to file: " + outPath);

		// Create File
		try {
			File myObj = new File(outPath);
			if (myObj.createNewFile()) {
				System.out.println("File created: " + myObj.getName());
			} else {
				System.out.println("File already exists.");
			}
		} catch (IOException e) {
			System.out.println("An error occurred.");
			e.printStackTrace();
		}

		// Write to File
		try {
			FileWriter myWriter = new FileWriter(outPath);
			myWriter.write(output);
			myWriter.close();
			System.out.println("Successfully wrote to the file.");
		} catch (IOException e) {
			System.out.println("An error occurred.");
			e.printStackTrace();
		}

		System.out.println(output);
	}

	private static ExportFormate ConvertExportFormat(String format) {

		format = format.toLowerCase();

		if (format.equals("-c") || format.equals("-csv")) {
			return ExportFormate.csv;
		} else if (format.equals("-j") || format.equals("-json")) {
			return ExportFormate.json;
		} else if (format.equals("-x") || format.equals("-xml")) {
			return ExportFormate.xml;
		} else if (format.equals("-t") || format.equals("-text")) {
			return ExportFormate.text;
		} else {
			throw new InvalidOpenTypeException("Could not convert " + format + " to an export format.");
		}

	}

	// ------------------------ Import Functionality
	// ------------------------------------------

	private static void importFromFile(String fileInPath, String FileFormat) {

	}

	private static ImportFormate ConvertImportFormat(String format) {
		format = format.toLowerCase();

		if (format.equals("-c") || format.equals("-csv")) {
			return ImportFormate.csv;
		} else if (format.equals("-j") || format.equals("-json")) {
			return ImportFormate.json;
		} else if (format.equals("-x") || format.equals("-xml")) {
			return ImportFormate.xml;
		} else {
			throw new InvalidOpenTypeException("Could not convert " + format + " to an import format.");
		}

	}
}

enum ExportFormate {
	csv, json, xml, text;
}

enum ImportFormate {
	csv, json, xml;
}

enum DeleteOptions {
	Users, Customers, Readings, All;
}