import static org.junit.Assert.assertEquals;

import org.jdbi.v3.core.Handle;
import org.jdbi.v3.core.Jdbi;
import org.jdbi.v3.guava.GuavaPlugin;
import org.jdbi.v3.sqlobject.SqlObjectPlugin;
import org.junit.Test;

import dev.hv.db.dao.ICustomerDAO;
import dev.hv.db.init.IDb;


public class ICustomerDAOTest{
    @Test
    public void delete_test() {
        Jdbi connection = IDb.getInstance().getJdbi();
        connection.installPlugin(new SqlObjectPlugin());
        connection.installPlugin(new GuavaPlugin());

        Handle handle = connection.open();
        try {
            handle.begin();
            int initialCount = handle.createQuery("SELECT COUNT(*) FROM customer")
                    .mapTo(Integer.class)
                    .findOnly();
            //handle.createUpdate("DELETE FROM customer WHERE id = :id")
            //        .bind("id", 1)
            //        .execute();
            final ICustomerDAO dao = handle.attach( ICustomerDAO.class);
            dao.delete((long)1);
            
            int finalCount = handle.createQuery("SELECT COUNT(*) FROM customer")
                    .mapTo(Integer.class)
                    .findOnly();
            assertEquals(initialCount - 1, finalCount);
            System.out.println(initialCount);
            System.out.println(finalCount);
            
        } finally {
            if (handle != null) {
                handle.rollback();
                handle.close();
            }
        }
    }

}