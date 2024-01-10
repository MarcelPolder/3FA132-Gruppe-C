package dev.hv.rest.util;

import java.util.ArrayList;
import java.util.List;

import org.jdbi.v3.core.Handle;
import org.jdbi.v3.core.Jdbi;
import org.jdbi.v3.guava.GuavaPlugin;
import org.jdbi.v3.sqlobject.SqlObjectPlugin;

import dev.hv.db.dao.ICustomerDAO;
import dev.hv.db.dao.IReadingDAO;
import dev.hv.db.init.IDb;
import dev.hv.db.model.DReading;
import dev.hv.rest.model.IRReading;
import dev.hv.rest.model.RReading;
import lombok.Generated;


public class ReadingsJsonUtil implements IReadings {
	private Jdbi connection = IDb.getInstance().getJdbi();

	public ReadingsJsonUtil() {
		connection.installPlugin(new SqlObjectPlugin());
		connection.installPlugin(new GuavaPlugin());
	}

	@Override
	public void close() {

	}

	@Override
	public void delete(Integer id) {
		Handle handle = connection.open();

		final IReadingDAO dao = handle.attach(IReadingDAO.class);

		dao.delete(id);
	}

	@Override
	public List<IRReading> getAll() {
		Handle handle = connection.open();

		final IReadingDAO dao = handle.attach(IReadingDAO.class);
		final ICustomerDAO cus_dao = handle.attach(ICustomerDAO.class);
		
		List<DReading> DatabaseUsers = dao.getAll();

		List<IRReading> RestUsers = new ArrayList();
		for (DReading read : DatabaseUsers) {
			read.setCustomer(cus_dao.findById(read.getCid()));
			
			RestUsers.add(new RReading(read));
		}

		return RestUsers;
	}

	@Override
	public IRReading getWithID(Integer id) {
		Handle handle = connection.open();

		final IReadingDAO dao = handle.attach(IReadingDAO.class);
		final ICustomerDAO cus_dao = handle.attach(ICustomerDAO.class);
		
		DReading read = dao.findById(id);
		read.setCustomer(cus_dao.findById(read.getCid()));

		return new RReading(read);
	}

	@Override
	public int insert(IRReading customer) {
		Handle handle = connection.open();

		final IReadingDAO dao = handle.attach(IReadingDAO.class);
		DReading dbUser = new DReading(customer);

		return dao.insert(dbUser);
	}

	@Override
	public void update(IRReading customer) {
		Handle handle = connection.open();

		final IReadingDAO dao = handle.attach(IReadingDAO.class);
		DReading dbUser = new DReading(customer);

		dao.update(dbUser);
	}

	@Override
	@Generated
	public List<IRReading> getAllfromCustomerID(Integer id) {
		Handle handle = connection.open();

		final IReadingDAO dao = handle.attach(IReadingDAO.class);
		final ICustomerDAO cus_dao = handle.attach(ICustomerDAO.class);
		
		List<IRReading> RestReadings = new ArrayList<IRReading>();
		
		List<DReading> allReadings = dao.getAll();
		for (DReading dbReading : allReadings) {
			dbReading.setCustomer(cus_dao.findById(dbReading.getCid()));

			
			if(dbReading.getCustomer().getId() == id) {
				RestReadings.add(new RReading(dbReading));
			}
		}
		
		return RestReadings;
	}

}
