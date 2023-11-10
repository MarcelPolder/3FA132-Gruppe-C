package dev.hv.db.model;

public interface IDReading {

   String getComment();

   IDCustomer getCustomer();

   String getDateOfReading();

   int getId();

   String getKindOfMeter();

   int getMeterCount();

   String getMeterId();

   int getSubstitute();

   String printDateofreading();

   void setComment(String comment);

   void setCustomer(IDCustomer customer);

   void setDateOfReading(String dateOfReading);

   void setId(int id);

   void setKindOfMeter(String kindOfMeter);

   void setMeterCount(int meterCount);

   void setMeterId(String meterId);

   void setSubstitute(int substitute);

}
