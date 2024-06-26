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
	@RegisterBeanMapper(DUser.class)
	DUser findById(@Bind("uid") int id);

	@SqlQuery("""
			SELECT * FROM user
			WHERE firstname||'.'||lastname= :username
			LIMIT 1
			""")
	@RegisterBeanMapper(DUser.class)
	DUser findByUsername(@Bind("username") String username);

	@Override
	@SqlQuery("""
			SELECT * FROM user
			""")
	@RegisterBeanMapper(DUser.class)
	List<DUser> getAll();

	@Override
	@SqlUpdate("""
			INSERT INTO user
			(firstname, lastname, password, token) 
			Values(:user.firstname, :user.lastname, :user.password, :user.token)
			""")
	int insert(@BindBean("user") DUser o);

	@Override
	@SqlUpdate("""
			UPDATE user
			SET
				firstname=:user.firstname,
				lastname=:user.lastname
			WHERE id = :uid
			""")
	void update(@Bind("uid") int id, @BindBean("user") DUser o);

	@Override
	@SqlUpdate("""
			UPDATE user
			SET 
				firstname=coalesce(:user.firstname, firstname),
				lastname=coalesce(:user.lastname, lastname),
				password=coalesce(:user.password, password),
				token=coalesce(:user.token, token)
			WHERE id = :user.id
			""")
	void update(@BindBean("user") DUser o);

}
