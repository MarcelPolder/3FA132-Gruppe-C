package dev.hv.db.dao;

import java.util.List;

import org.jdbi.v3.sqlobject.customizer.Bind;
import org.jdbi.v3.sqlobject.customizer.BindBean;
import org.jdbi.v3.sqlobject.statement.SqlQuery;
import org.jdbi.v3.sqlobject.statement.SqlUpdate;

import dev.hv.db.model.IDReading;

public interface IReadingDAO extends IDAO<IDReading> {

	@Override
	@SqlUpdate("""
			DELETE FROM reading
			WHERE id = :rid
			""")
	void delete(@Bind("rid") Long id);

	@Override
	@SqlUpdate("""
			DELETE FROM reading
			WHERE id = :read.Id
			""")
	void delete(@BindBean("read") IDReading o);

	@Override
	@SqlQuery("""
			SELECT * FROM reading
			WHERE id = :rid
			""")
	IDReading findById(@Bind("rid") Long id);

	@Override
	@SqlQuery("""
			SELECT * FROM reading
			""")
	List<IDReading> getAll();

	@Override
	@SqlUpdate("""
			INSERT INTO reading
			(id, comment, customer_id, date_of_reading, kind_of_meter, meter_count, meter_id, substitute) 
			Values(:read.Id, :read.Comment, :read.Customer.Id, :read.DateOfReading, :read.KindOfMeter, :read.MeterCount, :read.MeterId, :read.Substitute)
			""")
	long insert(@BindBean("read") IDReading o);

	@Override
	@SqlUpdate("""
			UPDATE reading
			SET (comment, customer_id, date_of_reading, kind_of_meter, meter_count, meter_id, substitute) 
			Values(:read.Comment, :read.Customer.Id, :read.DateOfReading, :read.KindOfMeter, :read.MeterCount, :read.MeterId, :read.Substitute)
			WHERE id = :rid
			""")
	void update(@Bind("rid") Long id, @BindBean("cus") IDReading o);

	@Override
	@SqlUpdate("""
			UPDATE reading
			SET (comment, customer_id, date_of_reading, kind_of_meter, meter_count, meter_id, substitute) 
			Values(:read.Comment, :read.Customer.Id, :read.DateOfReading, :read.KindOfMeter, :read.MeterCount, :read.MeterId, :read.Substitute)
			WHERE id = :read.id
			""")
	void update(@BindBean("read") IDReading o);

}
