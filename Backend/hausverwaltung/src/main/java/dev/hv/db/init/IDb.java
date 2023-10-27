package dev.hv.db.init;

import org.jdbi.v3.core.Jdbi;

import java.io.BufferedReader;
import java.io.FileInputStream;
import java.io.FileReader;
import java.io.IOException;
import java.util.Properties;

import org.jdbi.v3.core.Handle;

public class IDb implements IDbConnect {
	private Jdbi jdbiInstance;
	private static IDb instance;
	
	private IDb() {
	}
	
	public static IDb getInstance() {
		if (instance == null) {
			instance = new IDb();
		}
		return instance;
	}

	@Override
	public void createAllTables() {
		Jdbi db = this.getJdbi();
		Handle h = db.open();
		try {
			BufferedReader reader = new BufferedReader(new FileReader("src/main/resources/db/schema.sql"));
			int charCode = reader.read();
			String sql = "";
			while (charCode > 0) {
				sql += (char) charCode;
				charCode = reader.read();
				
				if ((char) charCode == ';') {
					h.execute(sql);
				}
			}
			reader.close();
			h.close();
		} catch (IOException e) {
			e.printStackTrace();
		}
	}

	@Override
	public Jdbi getJdbi() {
		if (this.jdbiInstance == null) {
			Properties appProps = new Properties();
			try {
				appProps.load(new FileInputStream("src/main/resources/app.properties"));
				this.jdbiInstance = this.getJdbi(appProps.getProperty("DB_URI"), appProps.getProperty("DB_USER"), appProps.getProperty("DB_PASS"));
			} catch (IOException e) {
				e.printStackTrace();
			}
		}
		return this.jdbiInstance;
	}

	@Override
	public Jdbi getJdbi(String uri, String user, String pw) {
		return Jdbi.create(uri, user, pw);
	}

	@Override
	public void removeAllTables() {
		Handle h = IDb.getInstance().getJdbi().open();
		h.execute("DROP TABLE customer; DROP TABLE reading; DROP TABLE user;");
		h.close();
	}
	
	public void importData() {
		Handle h = IDb.getInstance().getJdbi().open();
		try {
			BufferedReader reader = new BufferedReader(new FileReader("src/main/resources/db/data-2023-10-27.sql"));
			int charCode = reader.read();
			String sql = "";
			while (charCode > 0) {
				sql += (char) charCode;
				charCode = reader.read();
				
				if ((char) charCode == ';') {
					h.execute(sql);
				}
			}
			reader.close();
			h.close();
		} catch (IOException e) {
			e.printStackTrace();
		}
		
	}
}
