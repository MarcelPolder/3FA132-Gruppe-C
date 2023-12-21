package restTest;

import dev.hv.rest.Server;

import static org.junit.Assert.assertTrue;

import org.junit.Test;



public class RestServerTest {

    @Test
    public void startServerTest() {
        Server.main(null);
                assertTrue(true);

    }
}
