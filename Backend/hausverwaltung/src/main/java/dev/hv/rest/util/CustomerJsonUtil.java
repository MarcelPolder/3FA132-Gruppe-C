package dev.hv.rest.util;

import java.util.ArrayList;
import java.util.List;

import org.jdbi.v3.core.Jdbi;
import org.jdbi.v3.guava.GuavaPlugin;
import org.jdbi.v3.sqlobject.SqlObjectPlugin;
import org.jdbi.v3.core.Handle;

import dev.hv.db.dao.ICustomerDAO;
import dev.hv.db.init.IDb;
import dev.hv.db.model.DCustomer;
import dev.hv.rest.model.IRCustomer;
import dev.hv.rest.model.RCustomer;

public class CustomerJsonUtil implements ICustomers {

	private Jdbi connection = IDb.getInstance().getJdbi();

	public CustomerJsonUtil() {
		connection.installPlugin(new SqlObjectPlugin());
		connection.installPlugin(new GuavaPlugin());
	}

	@Override
	public void close() {

	}

	@Override
	public void delete(Integer id) {
		Handle handle = connection.open();

		final ICustomerDAO dao = handle.attach(ICustomerDAO.class);

		dao.delete(id);
	}

	@Override
	public List<IRCustomer> getAll() {
		Handle handle = connection.open();

		final ICustomerDAO dao = handle.attach(ICustomerDAO.class);
		List<DCustomer> DatabaseUsers = dao.getAll();

		List<IRCustomer> RestUsers = new ArrayList();
		for (DCustomer user : DatabaseUsers) {
			RestUsers.add(new RCustomer(user));
		}

		return RestUsers;
	}

	@Override
	public IRCustomer getWithID(Integer id) {
		Handle handle = connection.open();

		final ICustomerDAO dao = handle.attach(ICustomerDAO.class);

		return new RCustomer(dao.findById(id));
	}

	@Override
	public int insert(IRCustomer customer) {
		Handle handle = connection.open();

		final ICustomerDAO dao = handle.attach(ICustomerDAO.class);
		DCustomer dbUser = new DCustomer(customer.getFirstname(), customer.getLastname());

		return dao.insert(dbUser);
	}

	@Override
	public void update(IRCustomer customer) {
		Handle handle = connection.open();

		final ICustomerDAO dao = handle.attach(ICustomerDAO.class);
		DCustomer dbUser = new DCustomer(customer);

		dao.update(dbUser);
	}

}
