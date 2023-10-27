package dev.hv.db.dao;

import java.util.List;

import org.jdbi.v3.sqlobject.customizer.Bind;
import org.jdbi.v3.sqlobject.customizer.BindBean;
import org.jdbi.v3.sqlobject.statement.SqlQuery;
import org.jdbi.v3.sqlobject.statement.SqlUpdate;

import dev.hv.db.model.IDCustomer;

public interface ICustomerDAO extends IDAO<IDCustomer>{

	@Override
	@SqlUpdate("""
			DELETE FROM customer 
			WHERE id = :cid
			""")
	void delete(@Bind("cid") Long id); 

	@Override
	@SqlUpdate("""
			DELETE FROM customer
			WHERE id = :id
			""")
	void delete(@BindBean IDCustomer o);

	@Override
	@SqlQuery("""
			SELECT * FROM customer
			WHERE id = :cid
			""")
	IDCustomer findById(@Bind("cid") Long id);

	@Override
	@SqlQuery(""" 
			SELECT * FROM customer
			""")
	List<IDCustomer> getAll();

	@Override
	@SqlUpdate(""" 
			INSERT INTO customers
			(id, vorname, nachname) Values(:cus.id, :cus.vorname, :cus.nachname)
			""")
	long insert(@BindBean("cus") IDCustomer o);

	@Override
	@SqlUpdate(""" 
			UPDATE customers
			SET (vorname, nachname) Values(:cus.vorname, :cus.nachname)
			WHERE id = :cid
			""")
	void update(@Bind("cid") Long id, @BindBean("cus") IDCustomer o);

	@Override
	@SqlUpdate(""" 
			UPDATE customers
			SET (vorname, nachname) Values(:cus.vorname, :cus.nachname)
			WHERE id = :cus.id
			""")
	void update(IDCustomer o);
	

}
