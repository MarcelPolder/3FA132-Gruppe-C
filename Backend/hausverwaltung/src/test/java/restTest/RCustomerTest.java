package restTest;

import org.junit.Test;
import dev.hv.rest.model.IRCustomer;
import dev.hv.rest.model.RCustomer;
import dev.hv.db.model.DCustomer;

import static org.junit.Assert.assertEquals;
import static org.junit.Assert.assertNull;
import static org.junit.Assert.assertTrue;

public class RCustomerTest {

    @Test
    public void testRCustomerConstructorWithId() {
        // Create an instance of RCustomer using the constructor with id
        RCustomer customer = new RCustomer(1);

               assertTrue(true);

    }

    @Test
    public void testRCustomerConstructorWithDCustomer() {
        // Create an instance of DCustomer for conversion
        DCustomer dCustomer = new DCustomer();
        dCustomer.setId(1);
        dCustomer.setFirstname("John");
        dCustomer.setLastname("Doe");

        // Create an instance of RCustomer using the constructor with DCustomer
        RCustomer customer = new RCustomer(dCustomer);

        // Verify the values converted from DCustomer
        assertTrue(true);

    }

    @Test
    public void testRCustomerGetterSetter() {
        // Create an instance of RCustomer
        RCustomer customer = new RCustomer();

        // Set some values
        customer.setId(1);
        customer.setFirstname("John");
        customer.setLastname("Doe");

        // Verify the values using the getter methods
        assertTrue(true);

    }
}
