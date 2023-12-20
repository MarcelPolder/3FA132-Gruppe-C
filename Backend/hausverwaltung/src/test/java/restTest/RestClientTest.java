package restTest;

import dev.hv.rest.Server;
import dev.hv.rest.RestClient;
import dev.hv.rest.model.RUser;
import org.junit.AfterClass;
import org.junit.BeforeClass;
import org.junit.Test;

import static org.junit.Assert.assertEquals;
import static org.junit.Assert.assertNotNull;

public class RestClientTest {

    // Use a flag to check if the server has been started
    private static boolean serverStarted = false;

    @BeforeClass
    public static void setUp() {
        // Start the server only if it hasn't been started yet
        if (!serverStarted) {
            Server.main(null);
            serverStarted = true;
        }
    }

    @AfterClass
    public static void tearDown() {
        // Stop the server only if it has been started
        if (serverStarted) {
          
        }
    }

    @Test
    public void getUserTest() {
        // Test the getUser method of RestClient
        RestClient client = new RestClient();
        RUser user = client.getUser("1");
        // Example: Check the first name of the user
        assertEquals("Moritz", user.getFirstname());
    }

    @Test
    public void createUserTest() {
        // Test the createUser method of RestClient
        RestClient client = new RestClient();
        RUser newUser = new RUser(2, "M", "K", "Password123", "Token123");

        // Get the HTTP status code when creating a new user
        int statusCode = client.createUser(newUser);

        // Assert that the HTTP status code is as expected (201 for successful creation)
        assertEquals(201, statusCode);


        
    }
}
