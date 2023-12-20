package restTest;

import com.fasterxml.jackson.annotation.JsonTypeInfo;
import org.junit.Test;
import dev.hv.rest.model.IRReading; // Import the IRReading interface#
import dev.hv.rest.model.RReading;
import dev.hv.rest.model.RCustomer;


import static org.junit.Assert.assertEquals;
import static org.junit.Assert.assertNotNull;

import static org.junit.Assert.assertNull;

public class IRReadingTest {

    @Test
    public void testIRReadingMethods() {
        // Create an instance of a class implementing IRReading
        IRReading reading = new RReading();

        // Set some values
        reading.setComment("Sample comment");
        reading.setCustomer(new RCustomer()); 
        reading.setDateofreading("2023-01-01");
        reading.setId(1);
        reading.setKindofmeter("Electric");
        reading.setMetercount(100);
        reading.setMeterid("Meter123");
        reading.setSubstitute(2);

        // Verify the values using the getter methods
        assertEquals("Sample comment", reading.getComment());
        assertEquals("2023-01-01", reading.getDateofreading());
        assertEquals(Integer.valueOf(1), reading.getId());
        assertEquals("Electric", reading.getKindofmeter());
        assertEquals(Integer.valueOf(100), reading.getMetercount());
        assertEquals("Meter123", reading.getMeterid());
        assertEquals(Integer.valueOf(2), reading.getSubstitute());

        // Verify the customer
        assertNotNull(reading.getCustomer()); 
    
    }

    @Test
    public void testIRReadingDefaultValues() {
        // Create an instance of a class implementing IRReading
        IRReading reading = new RReading();

        assertNull(reading.getComment());
        assertNull(reading.getDateofreading());
        assertNull(reading.getId());
        assertNull(reading.getKindofmeter());
        assertNull(reading.getMetercount());
        assertNull(reading.getMeterid());
        assertNull(reading.getSubstitute());
        assertNull(reading.getCustomer());
    }
}
