package restTest.apiTest;
import dev.hv.rest.Server;


import static org.junit.Assert.assertEquals;
import static org.junit.Assert.assertNotNull;
import static org.junit.Assert.assertTrue;

import java.net.HttpURLConnection;
import java.net.URL;

import org.junit.AfterClass;
import org.junit.BeforeClass;
import org.junit.Test;

public class CustomerResourceTest {

    private static Thread serverThread;

    @BeforeClass
    public static void setUp() throws Exception {
        // Starte den Server in einem separaten Thread
        serverThread = new Thread(() -> {
            Server.main(null);
        });
        serverThread.start();

        // Warte eine Weile, um sicherzustellen, dass der Server gestartet ist
        Thread.sleep(2000);
    }

    @AfterClass
    public static void tearDown() throws Exception {
        // Beende den Server-Thread
        serverThread.interrupt();
        serverThread.join();
    }

    @Test
    public void testGetCustomerById() throws Exception {
        int customerId = 1;
        URL url = new URL("http://localhost:8080/rest/customers/get/" + customerId);
        
        HttpURLConnection connection = (HttpURLConnection) url.openConnection();
        connection.setRequestMethod("GET");
        
        int responseCode = connection.getResponseCode();
        
        assertEquals(HttpURLConnection.HTTP_OK, responseCode);
    }

    @Test
    public void testGetAllCustomers() throws Exception {
        URL url = new URL("http://localhost:8080/rest/customers/get");
        
        HttpURLConnection connection = (HttpURLConnection) url.openConnection();
        connection.setRequestMethod("GET");
        
        int responseCode = connection.getResponseCode();
        
        assertEquals(HttpURLConnection.HTTP_OK, responseCode);
    }
}
