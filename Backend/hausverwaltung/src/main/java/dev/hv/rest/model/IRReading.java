package dev.hv.rest.model;

import com.fasterxml.jackson.annotation.JsonTypeInfo;

@JsonTypeInfo(use = JsonTypeInfo.Id.CLASS)
public interface IRReading {

   String getComment();

   RCustomer getCustomer();

   String getDateofreading();

   Integer getId();

   String getKindofmeter();

   Integer getMetercount();

   String getMeterid();

   Integer getSubstitute();

   String printDateofreading();

   void setComment(String comment);

   void setCustomer(RCustomer customer);

   void setDateofreading(String dateOfReading);

   void setId(Integer id);

   void setKindofmeter(String kindOfMeter);

   void setMetercount(Integer meterCount);

   void setMeterid(String meterId);

   void setSubstitute(Integer substitute);

}
