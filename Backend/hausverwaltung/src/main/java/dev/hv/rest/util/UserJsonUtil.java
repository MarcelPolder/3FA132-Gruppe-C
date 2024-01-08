package dev.hv.rest.util;

import java.util.ArrayList;
import java.util.List;

import org.jdbi.v3.core.Jdbi;
import org.jdbi.v3.guava.GuavaPlugin;
import org.jdbi.v3.sqlobject.SqlObjectPlugin;
import org.jdbi.v3.core.Handle;

import dev.hv.db.dao.IUserDAO;
import dev.hv.db.init.IDb;
import dev.hv.db.model.DUser;
import dev.hv.db.model.IDUser;
import dev.hv.rest.model.IRUser;
import dev.hv.rest.model.RUser;

public class UserJsonUtil implements IUsers {

	private Jdbi connection = IDb.getInstance().getJdbi();

	public UserJsonUtil() {
		connection.installPlugin(new SqlObjectPlugin());
		connection.installPlugin(new GuavaPlugin());
	}

	@Override
	public void close() {

	}

	@Override
	public void delete(Integer id) {
		Handle handle = connection.open();

		final IUserDAO dao = handle.attach(IUserDAO.class);

		dao.delete(id);
	}

	@Override
	public List<IRUser> getAll() {
		Handle handle = connection.open();

		final IUserDAO dao = handle.attach(IUserDAO.class);
		List<DUser> DatabaseUsers = dao.getAll();

		List<IRUser> RestUsers = new ArrayList<>();
		for (DUser user : DatabaseUsers) {
			RestUsers.add(new RUser(user));
		}

		return RestUsers;
	}

	@Override
	public IRUser getWithID(Integer id) {
		Handle handle = connection.open();

		final IUserDAO dao = handle.attach(IUserDAO.class);

		return new RUser(dao.findById(id));
	}

	@Override
	public int insert(IRUser user) {
		Handle handle = connection.open();

		final IUserDAO dao = handle.attach(IUserDAO.class);
		DUser dbUser = new DUser(user);

		return dao.insert(dbUser);
	}

	@Override
	public void update(IRUser user) {
		Handle handle = connection.open();

		final IUserDAO dao = handle.attach(IUserDAO.class);
		DUser dbUser = new DUser(user);

		dao.update(dbUser);
	}

	public IRUser getWithUsername(String username) {
		Handle handle = connection.open();
		final IUserDAO dao = handle.attach(IUserDAO.class);
		DUser dbUser = dao.findByUsername(username);
		handle.close();
		if (dbUser == null) return null;
		return new RUser(dbUser);
	}

}
