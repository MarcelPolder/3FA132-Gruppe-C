package dev.hv.db.model;

public interface IDReading {

   String getComment();

   IDCustomer getCustomer();

   Long getDateOfReading();

   Long getId();

   String getKindOfMeter();

   Double getMeterCount();

   String getMeterId();

   Boolean getSubstitute();

   String printDateofreading();

   void setComment(String comment);

   void setCustomer(IDCustomer customer);

   void setDateOfReading(Long dateOfReading);

   void setId(Long id);

   void setKindOfMeter(String kindOfMeter);

   void setMeterCount(Double meterCount);

   void setMeterId(String meterId);

   void setSubstitute(Boolean substitute);

}
