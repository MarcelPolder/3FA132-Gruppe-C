package dev.hv.db.dao;

import java.util.List;

import org.jdbi.v3.sqlobject.config.RegisterBeanMapper;
import org.jdbi.v3.sqlobject.customizer.Bind;
import org.jdbi.v3.sqlobject.customizer.BindBean;
import org.jdbi.v3.sqlobject.statement.SqlQuery;
import org.jdbi.v3.sqlobject.statement.SqlUpdate;

import dev.hv.db.model.DCustomer;
import dev.hv.db.model.IDCustomer;

public interface ICustomerDAO extends IDAO<DCustomer> {

	@Override
	@SqlUpdate("""
			DELETE FROM customer
			WHERE id = :cid
			""")
	void delete(@Bind("cid") Long id);

	@Override
	@SqlUpdate("""
			DELETE FROM customer
			WHERE id = :cus.Id
			""")
	void delete(@BindBean("cus") DCustomer o);

	@Override
	@SqlQuery("""
			SELECT  c.id as id, c.vorname as firstname, c.nachname as lastname
			FROM customer c
			WHERE id = :cid
			""")
	@RegisterBeanMapper(DCustomer.class)
	DCustomer findById(@Bind("cid") Long id);

	@Override
	@SqlQuery("""
			SELECT  c.id as id, c.vorname as firstname, c.nachname as lastname
			FROM customer c			
			""")
	@RegisterBeanMapper(DCustomer.class)
	List<DCustomer> getAll();

	@Override
	@SqlUpdate("""
			INSERT INTO customers
			(id, vorname, nachname) 
			Values(:cus.Id, :cus.Vorname, :cus.Nachname)
			""")
	long insert(@BindBean("cus") DCustomer o);

	@Override
	@SqlUpdate("""
			UPDATE customers
			SET (vorname, nachname) 
			Values(:cus.Vorname, :cus.Nachname)
			WHERE id = :cid
			""")
	void update(@Bind("cid") Long id, @BindBean("cus") DCustomer o);

	@Override
	@SqlUpdate("""
			UPDATE customers
			SET (vorname, nachname) 
			Values(:cus.vorname, :cus.nachname)
			WHERE id = :cus.id
			""")
	void update(@BindBean("cus") DCustomer o);

}
