package restTest;

import org.junit.Test;
import dev.hv.db.model.DUser;
import dev.hv.rest.model.RUser;



import static org.junit.Assert.assertEquals;
import static org.junit.Assert.assertNull;

public class RUserTest {

    @Test
    public void testRUserConstructorWithIDUser() {
        // Create an instance of DUser for conversion
        DUser idUser = new DUser();
        idUser.setId(1);
        idUser.setFirstname("John");
        idUser.setLastname("Doe");
        idUser.setPassword("securePassword");
        idUser.setToken("exampleToken");

        // Create an instance of RUser using the constructor with DUser
        RUser user = new RUser(idUser);

        // Verify the values converted from DUser
        assertEquals(1, user.getId());
        assertEquals("John", user.getFirstname());
        assertEquals("Doe", user.getLastname());
        assertEquals("securePassword", user.getPassword());
        assertEquals("exampleToken", user.getToken());
    }

    @Test
    public void testRUserGetterSetter() {
        // Create an instance of RUser
        RUser user = new RUser();

        // Set some values
        user.setId(1);
        user.setFirstname("John");
        user.setLastname("Doe");
        user.setPassword("securePassword");
        user.setToken("exampleToken");

        // Verify the values using the getter methods
        assertEquals(1, user.getId());
        assertEquals("John", user.getFirstname());
        assertEquals("Doe", user.getLastname());
        assertEquals("securePassword", user.getPassword());
        assertEquals("exampleToken", user.getToken());
    }

    @Test
    public void testRUserDefaultValues() {
        // Create an instance of RUser
        RUser user = new RUser();

        // Verify default values (assuming your implementation initializes default values)
        assertEquals(0, user.getId());
        assertNull(user.getFirstname());
        assertNull(user.getLastname());
        assertNull(user.getPassword());
        assertNull(user.getToken());
    }

}
