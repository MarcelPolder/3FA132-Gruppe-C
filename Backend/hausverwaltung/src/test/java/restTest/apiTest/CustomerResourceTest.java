package restTest.apiTest;
import dev.hv.rest.resources.CustomerRessource;

import static org.junit.Assert.assertEquals;

import jakarta.ws.rs.core.Response;

import org.junit.AfterClass;
import org.junit.BeforeClass;
import org.junit.Test;

public class CustomerResourceTest {

    private static CustomerRessource customerResource;

    @BeforeClass
    public static void setUp() throws Exception {
        // Initialize the CustomerRessource class
        customerResource = new CustomerRessource();
    }

    @AfterClass
    public static void tearDown() throws Exception {
        // Cleanup or release any resources if needed
    }

    @Test
    public void testGetCustomerById() throws Exception {
        int customerId = 1;
        
        // Call the getCustomerById method directly and receive a Response object
        Response response = customerResource.getCustomerById(customerId);
        
        // Assert the status code in the response
        assertEquals(Response.Status.OK.getStatusCode(), response.getStatus());
    }

    @Test
    public void testGetAllCustomers() throws Exception {
        // Call the getAllCustomers method directly and receive a Response object
        Response response = customerResource.getAllCustomers();
        
        // Assert the status code in the response
        assertEquals(Response.Status.OK.getStatusCode(), response.getStatus());
    }
}
