package dev.hv.db.dao;

import java.util.List;

import org.jdbi.v3.sqlobject.config.RegisterBeanMapper;
import org.jdbi.v3.sqlobject.customizer.Bind;
import org.jdbi.v3.sqlobject.customizer.BindBean;
import org.jdbi.v3.sqlobject.statement.SqlQuery;
import org.jdbi.v3.sqlobject.statement.SqlUpdate;

import dev.hv.db.model.DCustomer;
import dev.hv.db.model.DUser;

public interface IUserDAO extends IDAO<DUser> {

	@Override
	@SqlUpdate("""
			DELETE FROM user
			WHERE id = :uid
			""")
	void delete(@Bind("uid") int id);

	@Override
	@SqlUpdate("""
			DELETE FROM user
			WHERE id = :user.id
			""")
	void delete(@BindBean("user") DUser o);

	@Override
	@SqlQuery("""
			SELECT * FROM user
			WHERE id = :uid
			""")
	@RegisterBeanMapper(DCustomer.class)
	DUser findById(@Bind("uid") int id);

	@Override
	@SqlQuery("""
			SELECT * FROM user
			""")
	@RegisterBeanMapper(DCustomer.class)
	List<DUser> getAll();

	@Override
	@SqlUpdate("""
			INSERT INTO user
			(id, vorname, nachname) 
			Values(:user.Id, :user.Vorname, :user.Nachname)
			""")
	int insert(@BindBean("user") DUser o);

	@Override
	@SqlUpdate("""
			UPDATE user
			SET (vorname, nachname) 
			Values(:user.Vorname, :user.Nachname)
			WHERE id = :uid
			""")
	void update(@Bind("uid") int id, @BindBean("user") DUser o);

	@Override
	@SqlUpdate("""
			UPDATE user
			SET (vorname, nachname) 
			Values(:user.Vorname, :user.Nachname)
			WHERE id = :user.Id
			""")
	void update(@BindBean("user") DUser o);

}
