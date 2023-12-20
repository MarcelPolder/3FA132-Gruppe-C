package restTest;

import static org.junit.Assert.assertEquals;
import org.junit.Test;

import dev.hv.rest.model.IRCustomer;
import dev.hv.rest.model.RCustomer;

public class IRCustomerTest {

    @Test
    public void testIRCustomerMethods() {
        // Create an instance of a class implementing IRCustomer (e.g., RCustomer)
        IRCustomer customer = new RCustomer();

        // Set some values
        customer.setFirstname("John");
        customer.setLastname("Doe");
        customer.setId(1);

        // Verify the values using the getter methods
        assertEquals("John", customer.getFirstname());
        assertEquals("Doe", customer.getLastname());
        assertEquals(Integer.valueOf(1), customer.getId());
    }
}
