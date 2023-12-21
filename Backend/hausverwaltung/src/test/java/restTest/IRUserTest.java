package restTest;

import com.fasterxml.jackson.annotation.JsonTypeInfo;
import org.junit.Test;
import dev.hv.rest.model.IRUser;
import dev.hv.rest.model.RUser;

import static org.junit.Assert.assertEquals;
import static org.junit.Assert.assertNull;

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
        assertEquals("John", user.getFirstname());
        assertEquals("Doe", user.getLastname());
        assertEquals(1, user.getId());
        assertEquals("securePassword", user.getPassword());
        assertEquals("exampleToken", user.getToken());
    }

    @Test
    public void testIRUserDefaultValues() {
        // Create an instance of a class implementing IRUser
        IRUser user = new RUser();

        // Verify default values (assuming your implementation initializes default values)
        assertNull(user.getFirstname());
        assertNull(user.getLastname());
        assertEquals(0, user.getId());
        assertNull(user.getPassword());
        assertNull(user.getToken());
    }
}
