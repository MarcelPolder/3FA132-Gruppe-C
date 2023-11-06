package dev.hv.db.model;

public interface IDUser {

   String getFirstname();

   int getId();

   String getLastname();

   String getPassword();

   String getToken();

   void setFirstname(String firstName);

   void setId(int id);

   void setLastname(String lastName);

   void setPassword(String password);

   void setToken(String token);
}
