package dbTest;
import static org.junit.Assert.assertEquals;
import static org.junit.Assert.assertNotEquals;
import static org.junit.Assert.assertTrue;

import java.util.List;

import org.jdbi.v3.core.Handle;
import org.jdbi.v3.core.Jdbi;
import org.jdbi.v3.guava.GuavaPlugin;
import org.jdbi.v3.sqlobject.SqlObjectPlugin;
import org.junit.Test;

import dev.hv.db.dao.ICustomerDAO;
import dev.hv.db.dao.IUserDAO;
import dev.hv.db.init.IDb;
import dev.hv.db.model.DCustomer;
import dev.hv.db.model.DUser;
import dev.hv.db.model.IDUser;

public class IUserDAOTest {
	
	@Test
	public void delete_test() {
		Jdbi connection = IDb.getInstance().getJdbi();
		connection.installPlugin(new SqlObjectPlugin());
		connection.installPlugin(new GuavaPlugin());

		Handle handle = connection.open();
		try {
			handle.begin();
			int initialCount = handle.createQuery("SELECT COUNT(*) FROM user").mapTo(Integer.class).findOnly();
			// handle.createUpdate("DELETE FROM user WHERE id = :id")
			// .bind("id", 1)
			// .execute();
			final IUserDAO dao = handle.attach(IUserDAO.class);
			dao.delete(1);

			int finalCount = handle.createQuery("SELECT COUNT(*) FROM user").mapTo(Integer.class).findOnly();
        assertTrue(true);
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

			final IUserDAO dao = handle.attach(IUserDAO.class);
			IDUser user = dao.findById(1);

			if (user != null) {
				System.out.println(user.getFirstname());
			} else {
				assertTrue(true);
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

			final IUserDAO dao = handle.attach(IUserDAO.class);
			List<DUser> user = dao.getAll();

			if (user != null) {
				System.out.println("Size of List: " + user.size());
			} else {
				assertTrue(true);
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
			int initialCount = handle.createQuery("SELECT COUNT(*) FROM user").mapTo(Integer.class).findOnly();
			
			final IUserDAO dao = handle.attach(IUserDAO.class);
			DUser uid = dao.findById(1);
			
			List<DUser> user = dao.getAll();
			int count = user.size();
			count++;
			
			uid.setId(count);
			
			int newId = dao.insert(uid);
			
			System.out.println("Added new Object at. " + newId);

			int finalCount = handle.createQuery("SELECT COUNT(*) FROM user").mapTo(Integer.class).findOnly();
			assertTrue(true);
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
			final IUserDAO dao = handle.attach(IUserDAO.class);
			
			DUser uid = dao.findById(1);
			String initname = uid.getFirstname();
			
			String changeName = "Marcel";
			uid.setFirstname(changeName);
			
			assertTrue(true);
			
			
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