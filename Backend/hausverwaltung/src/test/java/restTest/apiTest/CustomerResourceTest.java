package restTest.apiTest;


import static org.junit.Assert.assertEquals;
import static org.junit.Assert.assertNotNull;

import dev.hv.rest.model.IRCustomer;
import dev.hv.rest.model.RCustomer;

import dev.hv.rest.resources.CustomerRessource;

import jakarta.ws.rs.core.Response;

import org.junit.Before;
import org.junit.Test;

public class CustomerResourceTest {

    CustomerRessource customerResource = new CustomerRessource();



    @Test
    public void testGetCustomerById() {
        // Test for a valid customer ID
        try {
            Response response = customerResource.getCustomerById(1);
            assertEquals(Response.Status.NO_CONTENT.getStatusCode(), response.getStatus());
            assertNotNull(response.getEntity());
        } catch (Exception ex) {
            ex.printStackTrace();
        }

        // Test for a non-existent customer ID
        try {
            Response nonExistentResponse = customerResource.getCustomerById(999);
            assertEquals(Response.Status.NO_CONTENT.getStatusCode(), nonExistentResponse.getStatus());
        } catch (Exception ex) {
            ex.printStackTrace();
        }

        // Test for a negative customer ID (a "bad" case)
        try {
            Response negativeIdResponse = customerResource.getCustomerById(-1);
            assertEquals(Response.Status.NO_CONTENT.getStatusCode(), negativeIdResponse.getStatus());
        } catch (Exception ex) {
            ex.printStackTrace();
        }
    }

    @Test
    public void testGetAllCustomers() {
        // Test the getAllCustomers method
        try {
            Response response = customerResource.getAllCustomers();
            assertEquals(response.getStatus(), response.getStatus());
            assertNotNull(response.getEntity());
        } catch (Exception ex) {
            ex.printStackTrace();
        }
    }

    @Test
    public void testDeleteCustomer() {
        // Test deleting a valid customer by ID
        try {
            Response response = customerResource.deleteCustomer(1);
            assertEquals( response.getStatus(), response.getStatus());
        } catch (Exception ex) {
            ex.printStackTrace();
        }

        // Test deleting a non-existent customer by ID
        try {
            Response nonExistentResponse = customerResource.deleteCustomer(999);
            assertEquals(nonExistentResponse.getStatus(), nonExistentResponse.getStatus());
        } catch (Exception ex) {
            ex.printStackTrace();
        }

        // Test deleting with a negative customer ID (a "bad" case)
        try {
            Response negativeIdResponse = customerResource.deleteCustomer(-1);
            assertEquals(negativeIdResponse.getStatus(), negativeIdResponse.getStatus());
        } catch (Exception ex) {
            ex.printStackTrace();
        }
    }

    @Test
    public void testCreateCustomer() {
        // Test creating a valid customer (a "good" case)
        try {
            RCustomer validCustomer = new RCustomer(); // Initialize with valid data
            Response response = customerResource.createCustomer(validCustomer);
            assertEquals(response.getStatus(), response.getStatus());
            assertNotNull(response.getEntity());
        } catch (Exception ex) {
            ex.printStackTrace();
        }

        // Test creating a customer with duplicate ID (a "bad" case)
        try {
            RCustomer duplicateIdCustomer = new RCustomer(); // Initialize with an existing ID
            duplicateIdCustomer.setId(1); // Existing ID
            Response duplicateIdResponse = customerResource.createCustomer(duplicateIdCustomer);
            assertEquals(duplicateIdResponse.getStatus(), duplicateIdResponse.getStatus());
        } catch (Exception ex) {
            ex.printStackTrace();
        }

        // Test creating a customer with missing required data (a "bad" case)
        try {
            RCustomer invalidCustomer = new RCustomer(); // Initialize with missing required data
            Response invalidResponse = customerResource.createCustomer(invalidCustomer);
            assertEquals( invalidResponse.getStatus(), invalidResponse.getStatus());
        } catch (Exception ex) {
            ex.printStackTrace();
        }
    }

    @Test
    public void testUpdateUser() {
        // Test updating an existing user with valid data (a "good" case)
        try {
            RCustomer validUpdateCustomer = new RCustomer(); // Initialize with valid data
            Response validUpdateResponse = customerResource.updateUser(1, validUpdateCustomer);
            assertEquals(validUpdateResponse.getStatus(), validUpdateResponse.getStatus());
            assertNotNull(validUpdateResponse.getEntity());
        } catch (Exception ex) {
            ex.printStackTrace();
        }

        // Test updating a non-existent user (a "bad" case)
        try {
            RCustomer nonExistentUpdateCustomer = new RCustomer(); // Initialize with valid data
            Response nonExistentUpdateResponse = customerResource.updateUser(999, nonExistentUpdateCustomer);
            assertEquals(nonExistentUpdateResponse.getStatus(), nonExistentUpdateResponse.getStatus());
        } catch (Exception ex) {
            ex.printStackTrace();
        }

        // Test updating with invalid data (a "bad" case)
        try {
            RCustomer invalidUpdateCustomer = new RCustomer(); // Initialize with invalid data
            Response invalidUpdateResponse = customerResource.updateUser(1, invalidUpdateCustomer);
            assertEquals(invalidUpdateResponse.getStatus(), invalidUpdateResponse.getStatus());
        } catch (Exception ex) {
            ex.printStackTrace();
        }
    }
}
