package dev.hv.db.dao;

import java.util.List;

public interface IDAO<T> {

   // DELETE
   void delete(int id);

   // DELETE
   void delete(T o);

   // READ
   T findById(int id);

   // READ
   List<T> getAll();

   // CREATE
   int insert(T o);

   // UPDATE
   void update(int id, T o);

   // UPDATE
   void update(T o);
}
