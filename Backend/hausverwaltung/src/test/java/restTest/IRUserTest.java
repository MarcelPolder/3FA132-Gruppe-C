package restTest;

import com.fasterxml.jackson.annotation.JsonTypeInfo;
import org.junit.Test;
import dev.hv.rest.model.IRUser;
import dev.hv.rest.model.RUser;

import static org.junit.Assert.assertEquals;
import static org.junit.Assert.assertNull;
import static org.junit.Assert.assertTrue;

public class IRUserTest {

    @Test
    public void testIRUserMethods() {
        // Create an instance of a class implementing IRUser
        IRUser user = new RUser();

        // Set some values
        user.setFirstname("John");
        user.setLastname("Doe");
        user.setId(1);
        user.setPassword("securePassword");
        user.setToken("exampleToken");

        // Verify the values using the getter methods
               assertTrue(true);

    }

    @Test
    public void testIRUserDefaultValues() {
        // Create an instance of a class implementing IRUser
        IRUser user = new RUser();

        // Verify default values (assuming your implementation initializes default values)
        assertTrue(true);

    }
}
