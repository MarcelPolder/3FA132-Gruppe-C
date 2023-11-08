import static org.junit.Assert.assertEquals;
import static org.junit.Assert.assertFalse;
import static org.junit.Assert.assertNotEquals;
import static org.junit.Assert.assertNotNull;
import static org.junit.Assert.fail;

import java.util.List;
import org.jdbi.v3.core.Handle;
import org.jdbi.v3.core.Jdbi;
import org.jdbi.v3.guava.GuavaPlugin;
import org.jdbi.v3.sqlobject.SqlObjectPlugin;
import org.junit.Test;

import dev.hv.db.dao.ICustomerDAO;
import dev.hv.db.dao.IReadingDAO;
import dev.hv.db.init.IDb;
import dev.hv.db.model.DReading;
import dev.hv.db.model.IDCustomer;
import dev.hv.db.model.DCustomer;
import dev.hv.db.model.IDReading;

public class IReadingDAOTest {

    @Test
    public void deleteReadingTest() {
        Jdbi jdbi = IDb.getInstance().getJdbi();
        jdbi.installPlugin(new SqlObjectPlugin());

        try (Handle handle = jdbi.open()) {
            handle.begin();
            int initialCount = handle.createQuery("SELECT COUNT(*) FROM reading").mapTo(Integer.class).findOnly();

            IReadingDAO dao = handle.attach(IReadingDAO.class);
            dao.delete(1);

            int finalCount = handle.createQuery("SELECT COUNT(*) FROM reading").mapTo(Integer.class).findOnly();
            assertEquals(initialCount - 1, finalCount);

            handle.rollback();  // Rollback any changes made during this test
        }
    }

    @Test
	public void findbyReadingtest() {
		Jdbi connection = IDb.getInstance().getJdbi();
		connection.installPlugin(new SqlObjectPlugin());
		connection.installPlugin(new GuavaPlugin());

		Handle handle = connection.open();
		try {
			handle.begin();

			final IReadingDAO dao = handle.attach(IReadingDAO.class);
			IDReading reading = dao.findById((1));

			if (reading != null) {
				System.out.println(reading.getKindOfMeter());
			} else {
				assertEquals(reading, 1);
			}

		} finally {
			if (handle != null) {
				handle.rollback();
				handle.close();
			}
		}
	}
    @Test
    public void getAllReadingTest() {
    Jdbi jdbi = IDb.getInstance().getJdbi();
    jdbi.installPlugin(new SqlObjectPlugin());
    jdbi.installPlugin(new GuavaPlugin());

    Handle handle = null;
    try {
        handle = jdbi.open();
        handle.begin();

        final IReadingDAO dao = handle.attach(IReadingDAO.class);
        List<DReading> readings = dao.getAll();

        assertNotNull("Readings list should not be null", readings);
        assertFalse("Readings list should not be empty", readings.isEmpty());

        System.out.println("Size of List: " + readings.size());

        handle.commit(); // Commit the changes if all operations were successful
    } catch (Exception e) {
        if (handle != null) {
            handle.rollback(); // Rollback in case of an exception
        }
        e.printStackTrace(); // Log the exception
        fail("An exception was thrown during the test: " + e.getMessage());
    } finally {
        if (handle != null && !handle.isClosed()) {
            handle.close(); // Always close the handle
        }
    }
}
@Test
	public void insert_test() {
		Jdbi connection = IDb.getInstance().getJdbi();
		connection.installPlugin(new SqlObjectPlugin());
		connection.installPlugin(new GuavaPlugin());

		Handle handle = connection.open();
		try {
			handle.begin();
			int initialCount = handle.createQuery("SELECT COUNT(*) FROM reading").mapTo(Integer.class).findOnly();
			
			final IReadingDAO dao = handle.attach(IReadingDAO.class);
			DReading cus = dao.findById(1);
			
			List<DReading> reading = dao.getAll();
			int count = reading.size();
			count++;
			
            DCustomer customer = new DCustomer(10000, "Max", "Mustermann");

			cus.setCustomer(customer);

			cus.setId(count);

			int newId = dao.insert(cus);
			
			System.out.println("Added new Object at. " + newId);

			int finalCount = handle.createQuery("SELECT COUNT(*) FROM reading").mapTo(Integer.class).findOnly();
			assertEquals(initialCount + 1, finalCount);
			System.out.println(initialCount);
			System.out.println(finalCount);

		} finally {
			if (handle != null) {
				handle.rollback();
				handle.close();
				System.out.println("Rolled Back Changes");
			}
		}
	}
	@Test
	public void update_test() {
		Jdbi connection = IDb.getInstance().getJdbi();
		connection.installPlugin(new SqlObjectPlugin());
		connection.installPlugin(new GuavaPlugin());

		Handle handle = connection.open();
		try {
			handle.begin();			
			final IReadingDAO dao = handle.attach(IReadingDAO.class);
			
			DReading reading = dao.findById(1);
			String initname = reading.getKindOfMeter();
			
			String changeName = "Wasser2";
			reading.setKindOfMeter(changeName);
			
			assertNotEquals(initname, changeName);
			
			
			System.out.println("Old Name: " + initname);
			System.out.println("New Name: " + changeName);

		} finally {
			if (handle != null) {
				handle.rollback();
				handle.close();
				System.out.println("Rolled Back Changes");
			}
		}
	}
	
	
	
}