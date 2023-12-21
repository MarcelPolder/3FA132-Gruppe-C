package dbTest;

import static org.junit.Assert.assertTrue;

import java.util.List;
import java.util.Map;

import org.jdbi.v3.core.Handle;
import org.junit.Test;

import dev.hv.db.init.IDb;

/**
 * AppTest
 */
public class AppTest {

	@Test
	public void testDatabaseCustomer() {
		Handle h = IDb.getInstance().getJdbi().open();
		List<Map<String, Object>> result = h.createQuery("SELECT * FROM customer;").mapToMap().list();
		h.close();
        assertTrue(true);
	}
	
	@Test
	public void testDatabaseReading() {
		Handle h = IDb.getInstance().getJdbi().open();
		List<Map<String, Object>> result = h.createQuery("SELECT * FROM reading;").mapToMap().list();
		h.close();
        assertTrue(true);
	}
}