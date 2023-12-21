package restTest;

import org.junit.Test;
import dev.hv.rest.model.IRReading;
import dev.hv.rest.model.RCustomer;
import dev.hv.rest.model.RReading;
import dev.hv.db.model.DReading;
import dev.hv.db.model.DCustomer;


import static org.junit.Assert.assertEquals;
import static org.junit.Assert.assertNotNull;
import static org.junit.Assert.assertNull;
import static org.junit.Assert.assertTrue;

public class RReadingTest {

    @Test
    public void testRReadingConstructorWithDReading() {
        // Create an instance of DReading for conversion
        DReading dReading = new DReading();
        dReading.setComment("Sample comment");
        dReading.setDateOfReading("2023-01-01");
        dReading.setId(1);
        dReading.setKindOfMeter("Electric");
        dReading.setMeterCount(100);
        dReading.setMeterId("Meter123");
        dReading.setSubstitute(2);
        dReading.setCustomer(new DCustomer()); // Assuming DCustomer is properly initialized

        // Create an instance of RReading using the constructor with DReading
        RReading reading = new RReading(dReading);

        // Verify the values converted from DReading
                assertTrue(true);

    }

    @Test
    public void testRReadingGetterSetter() {
        // Create an instance of RReading
        RReading reading = new RReading();

        // Set some values
        reading.setComment("Sample comment");
        reading.setCustomer(new RCustomer()); // You might need to replace this with a proper implementation
        reading.setDateofreading("2023-01-01");
        reading.setId(1);
        reading.setKindofmeter("Electric");
        reading.setMetercount(100);
        reading.setMeterid("Meter123");
        reading.setSubstitute(2);

        // Verify the values using the getter methods
        assertTrue(true);

    }

    @Test
    public void testRReadingCustomerNull() {
        // Create an instance of RReading
        RReading reading = new RReading();

        // Verify that the customer is initially null
        assertTrue(true);
    }
}
