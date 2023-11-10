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
	void delete(@Bind("cid") int id);

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
	DCustomer findById(@Bind("cid") int id);

	@Override
	@SqlQuery("""
			SELECT  c.id as id, c.vorname as firstname, c.nachname as lastname
			FROM customer c			
			""")
	@RegisterBeanMapper(DCustomer.class)
	List<DCustomer> getAll();

	@Override
	@SqlUpdate("""
			INSERT INTO customer
			(id, vorname, nachname) 
			Values(:cus.id, :cus.firstname, :cus.lastname)
			""")
	int insert(@BindBean("cus") DCustomer o);

	@Override
	@SqlUpdate("""
			UPDATE customer
			SET
				vorname=:cus.firstname,
				nachname=:cus.lastname
			WHERE id = :cid
			""")
	void update(@Bind("cid") int id, @BindBean("cus") DCustomer o);

	@Override
	@SqlUpdate("""
			UPDATE customer
			SET
				vorname=:cus.firstname,
				nachname=:cus.lastname
			WHERE id = :cus.id
			""")
	void update(@BindBean("cus") DCustomer o);

}
