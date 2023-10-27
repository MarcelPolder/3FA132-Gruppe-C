package dev.hv.db.init;

import org.jdbi.v3.core.Jdbi;
import org.jdbi.v3.core.Handle;

public class IDb implements IDbConnect {
	private Jdbi instance;

	@Override
	public void createAllTables() {
	}

	@Override
	public Jdbi getJdbi() {
		return this.instance;
	}

	@Override
	public Jdbi getJdbi(String uri, String user, String pw) {
		if (this.instance == null) {
			this.instance = Jdbi.create(uri);
		}
		return this.instance;
	}

	@Override
	public void removeAllTables() {
	}

	public static void main(String[] args) {
		IDb idb = new IDb();
		Jdbi t = idb.getJdbi("jdbc:sqlite:./src/main/resources/db/database", "", "");
		Handle h = t.open();
		System.out.println(h.execute("SELECT * FROM customer;"));
	}
}
