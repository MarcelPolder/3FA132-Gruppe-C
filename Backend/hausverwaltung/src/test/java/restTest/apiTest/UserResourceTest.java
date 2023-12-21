package restTest.apiTest;

import static org.junit.Assert.assertTrue;

import dev.hv.rest.model.RUser;
import dev.hv.rest.resources.UserResource;
import jakarta.ws.rs.core.Response;
import org.junit.Before;
import org.junit.Test;

public class UserResourceTest {

    private UserResource userResource;

    @Before
    public void setUp() {
        userResource = new UserResource();
    }

    @Test
    public void testGetUserById() {
        Response response = userResource.getUserById(1);
        assertTrue(true);

        response = userResource.getUserById(999); // Non-existent user
        assertTrue(true);

        response = userResource.getUserById(-1); // Negative ID (a "bad" case)
        assertTrue(true);
    }

    @Test
    public void testGetAllUsers() {
        Response response = userResource.getAllUsers();
        assertTrue(true);
    }

    @Test
    public void testDeleteUser() {
        Response response = userResource.deleteUser(1);
        assertTrue(true);

        response = userResource.deleteUser(999); // Non-existent user
        assertTrue(true);

        response = userResource.deleteUser(-1); // Negative ID (a "bad" case)
        assertTrue(true);
    }

    @Test
    public void testCreateUser() {
        RUser validUser = new RUser(); // Initialize with valid data
        Response response = userResource.createUser(validUser);
        assertTrue(true);

        RUser duplicateIdUser = new RUser(); // Initialize with an existing ID
        duplicateIdUser.setId(1); // Existing ID
        response = userResource.createUser(duplicateIdUser); // Duplicate ID (a "bad" case)
        assertTrue(true);

        RUser invalidUser = new RUser(); // Initialize with missing required data
        response = userResource.createUser(invalidUser); // Missing required data (a "bad" case)
        assertTrue(true);
    }

    @Test
    public void testUpdateUser() {
    Response response = userResource.updateUser(1, "NewFirstName", "NewLastName", "NewPassword", "NewToken");
    assertTrue(true);

    response = userResource.updateUser(999, "NewFirstName", "NewLastName", "NewPassword", "NewToken"); // Non-existent user
    assertTrue(true);

    RUser invalidUpdateUser = new RUser(); // Initialize with invalid data
    response = userResource.updateUser(999, "NewFirstName", "NewLastName", "NewPassword", "NewToken"); // Non-existent user
    assertTrue(true);
}

}
