package restTest.apiTest;

import dev.hv.rest.model.RReading;
import dev.hv.rest.resources.ReadingResource;
import org.junit.Test;

public class ReadingResourceTest {

    @Test
    public void testDummyReadingResource() {
        ReadingResource resource = new ReadingResource();

        // Aufruf der getUserById-Methode
        resource.getUserById(1); // Dummy-Daten

        // Aufruf der getAllUsers-Methode
        resource.getAllUsers();

        // Aufruf der createReading-Methode
        RReading newReading = new RReading(); // Erstellen Sie ein geeignetes RReading-Objekt
        resource.createReading(newReading);

        // Aufruf der deleteReading-Methode
        resource.deleteReading(1);

        // Aufruf der updateUser-Methode
        RReading updateReading = new RReading(); // Erstellen Sie ein geeignetes RReading-Objekt
        resource.updateUser(1, updateReading);
    }
}
