package otherTests;


import org.junit.Test;
import dev.hv.rest.model.IRCustomer;
import dev.hv.rest.model.RCustomer;
import dev.hv.console.StartHV;
import dev.hv.db.model.DCustomer;

import static org.junit.Assert.assertEquals;
import static org.junit.Assert.assertNull;

public class startHVTest {

   


    @Test
    public void testStartHV() {
        // Creating an object of StartHV
        StartHV startHV = new StartHV();

        // Testing the main method with no arguments
        String[] noArgs = {};
        StartHV.main(noArgs);
        

        StartHV.printHelp();

        // Testing the main method with '-h' argument
        String[] helpArgs = {"-h"};
        StartHV.main(helpArgs);
       

        // Testing the main method with other arguments
        String[] otherArgs = {"arg1", "arg2"};
        StartHV.main(otherArgs);
        
    }
}
