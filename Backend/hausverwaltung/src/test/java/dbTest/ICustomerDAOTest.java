import static org.junit.Assert.assertEquals;
import static org.junit.Assert.assertNotEquals;

import java.util.List;

import org.jdbi.v3.core.Handle;
import org.jdbi.v3.core.Jdbi;
import org.jdbi.v3.guava.GuavaPlugin;
import org.jdbi.v3.sqlobject.SqlObjectPlugin;
import org.junit.Test;

import dev.hv.db.dao.ICustomerDAO;
import dev.hv.db.init.IDb;
import dev.hv.db.model.DCustomer;
import dev.hv.db.model.IDCustomer;

public class ICustomerDAOTest {
	
	@Test
	public void delete_test() {
		Jdbi connection = IDb.getInstance().getJdbi();
		connection.installPlugin(new SqlObjectPlugin());
		connection.installPlugin(new GuavaPlugin());

		Handle handle = connection.open();
		try {
			handle.begin();
			int initialCount = handle.createQuery("SELECT COUNT(*) FROM customer").mapTo(Integer.class).findOnly();
			// handle.createUpdate("DELETE FROM customer WHERE id = :id")
			// .bind("id", 1)
			// .execute();
			final ICustomerDAO dao = handle.attach(ICustomerDAO.class);
			dao.delete(1);

			int finalCount = handle.createQuery("SELECT COUNT(*) FROM customer").mapTo(Integer.class).findOnly();
			assertEquals(initialCount - 1, finalCount);
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
	public void findby_test() {
		Jdbi connection = IDb.getInstance().getJdbi();
		connection.installPlugin(new SqlObjectPlugin());
		connection.installPlugin(new GuavaPlugin());

		Handle handle = connection.open();
		try {
			handle.begin();

			final ICustomerDAO dao = handle.attach(ICustomerDAO.class);
			IDCustomer customer = dao.findById(1);

			if (customer != null) {
				System.out.println(customer.getFirstname());
			} else {
				assertEquals(customer, 1);
			}

		} finally {
			if (handle != null) {
				handle.rollback();
				handle.close();
			}
		}
	}

	@Test
	public void getAll_test() {
		Jdbi connection = IDb.getInstance().getJdbi();
		connection.installPlugin(new SqlObjectPlugin());
		connection.installPlugin(new GuavaPlugin());

		Handle handle = connection.open();
		try {
			handle.begin();

			final ICustomerDAO dao = handle.attach(ICustomerDAO.class);
			List<DCustomer> customer = dao.getAll();

			if (customer != null) {
				System.out.println("Size of List: " + customer.size());
			} else {
				assertEquals(customer, 1);
			}

		} finally {
			if (handle != null) {
				handle.rollback();
				handle.close();
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
			int initialCount = handle.createQuery("SELECT COUNT(*) FROM customer").mapTo(Integer.class).findOnly();
			
			final ICustomerDAO dao = handle.attach(ICustomerDAO.class);
			DCustomer cus = dao.findById(1);
			
			List<DCustomer> customer = dao.getAll();
			int count = customer.size();
			count++;
			
			cus.setId(count);
			
			int newId = dao.insert(cus);
			
			System.out.println("Added new Object at. " + newId);

			int finalCount = handle.createQuery("SELECT COUNT(*) FROM customer").mapTo(Integer.class).findOnly();
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
			final ICustomerDAO dao = handle.attach(ICustomerDAO.class);
			
			DCustomer cus = dao.findById(1);
			String initname = cus.getFirstname();
			
			String changeName = "Marcel";
			cus.setFirstname(changeName);
			
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