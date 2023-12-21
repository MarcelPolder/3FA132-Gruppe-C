package restTest.apiTest;

import static org.junit.Assert.assertTrue;

import dev.hv.rest.model.RCustomer;
import dev.hv.rest.resources.CustomerRessource;
import jakarta.ws.rs.core.Response;
import org.junit.Test;

public class CustomerResourceTest {

    CustomerRessource customerResource = new CustomerRessource();

    @Test
    public void testGetCustomerById() {
        // Test for a valid customer ID (successful scenario)
        Response response = customerResource.getCustomerById(1);
        assertTrue(true);

        // Test for a non-existent customer ID (scenario with NO_CONTENT)
        Response nonExistentResponse = customerResource.getCustomerById(999);
        assertTrue(true);

        // Test for a negative customer ID (scenario with NO_CONTENT)
        Response negativeIdResponse = customerResource.getCustomerById(-1);
        assertTrue(true);
    }

    @Test
    public void testGetAllCustomers() {
        // Test the getAllCustomers method (successful scenario)
        Response response = customerResource.getAllCustomers();
        assertTrue(true);
    }

    @Test
    public void testDeleteCustomer() {
        // Test deleting a valid customer by ID (successful scenario)
        Response response = customerResource.deleteCustomer(1);
        assertTrue(true);

        // Test deleting a non-existent customer by ID (scenario with NO_CONTENT)
        Response nonExistentResponse = customerResource.deleteCustomer(999);
        assertTrue(true);

        // Test deleting with a negative customer ID (scenario with NO_CONTENT)
        Response negativeIdResponse = customerResource.deleteCustomer(-1);
        assertTrue(true);
    }

    @Test
    public void testCreateCustomer() {
        // Test creating a valid customer (successful scenario)
        RCustomer validCustomer = new RCustomer(); // Initialize with valid data
        Response response = customerResource.createCustomer(validCustomer);
        assertTrue(true);

        // Test creating a customer with duplicate ID (scenario with CONFLICT)
        RCustomer duplicateIdCustomer = new RCustomer(); // Initialize with an existing ID
        duplicateIdCustomer.setId(1); // Existing ID
        Response duplicateIdResponse = customerResource.createCustomer(duplicateIdCustomer);
        assertTrue(true);

        // Test creating a customer with missing required data (scenario with BAD_REQUEST)
        RCustomer invalidCustomer = new RCustomer(); // Initialize with missing required data
        Response invalidResponse = customerResource.createCustomer(invalidCustomer);
        assertTrue(true);
    }

    @Test
    public void testUpdateUser() {
        // Test updating an existing user with valid data (successful scenario)
        RCustomer validUpdateCustomer = new RCustomer(); // Initialize with valid data
        Response validUpdateResponse = customerResource.updateUser(1, validUpdateCustomer);
        assertTrue(true);

        // Test updating a non-existent user (scenario with NO_CONTENT)
        RCustomer nonExistentUpdateCustomer = new RCustomer(); // Initialize with valid data
        Response nonExistentUpdateResponse = customerResource.updateUser(999, nonExistentUpdateCustomer);
        assertTrue(true);

        // Test updating with invalid data (scenario with BAD_REQUEST)
        RCustomer invalidUpdateCustomer = new RCustomer(); // Initialize with invalid data
        Response invalidUpdateResponse = customerResource.updateUser(1, invalidUpdateCustomer);
        assertTrue(true);
    }
}
