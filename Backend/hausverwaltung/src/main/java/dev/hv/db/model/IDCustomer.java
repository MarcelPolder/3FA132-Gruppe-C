package dev.hv.db.model;

public interface IDCustomer {

   String getFirstname();

   int getId();

   String getLastname();

   void setFirstname(String firstName);

   void setId(int id);

   void setLastname(String lastName);
}
