package dev.hv.db.dao;

import java.util.List;

import org.jdbi.v3.sqlobject.customizer.Bind;
import org.jdbi.v3.sqlobject.customizer.BindBean;
import org.jdbi.v3.sqlobject.statement.SqlQuery;
import org.jdbi.v3.sqlobject.statement.SqlUpdate;

import dev.hv.db.model.DReading;

public interface IReadingDAO extends IDAO<DReading> {

	@Override
	@SqlUpdate("""
			DELETE FROM reading
			WHERE id = :rid
			""")
	void delete(@Bind("rid") int id);

	@Override
	@SqlUpdate("""
			DELETE FROM reading
			WHERE id = :read.Id
			""")
	void delete(@BindBean("read") DReading o);

	@Override
	@SqlQuery("""
			SELECT * FROM reading
			WHERE id = :rid
			""")
	DReading findById(@Bind("rid") int id);

	@Override
	@SqlQuery("""
			SELECT * FROM reading
			""")
	List<DReading> getAll();

	@Override
	@SqlUpdate("""
			INSERT INTO reading
			(id, comment, customer_id, date_of_reading, kind_of_meter, meter_count, meter_id, substitute) 
			Values(:read.Id, :read.Comment, :read.Customer.Id, :read.DateOfReading, :read.KindOfMeter, :read.MeterCount, :read.MeterId, :read.Substitute)
			""")
	int insert(@BindBean("read") DReading o);

	@Override
	@SqlUpdate("""
			UPDATE reading
			SET (comment, customer_id, date_of_reading, kind_of_meter, meter_count, meter_id, substitute) 
			Values(:read.Comment, :read.Customer.Id, :read.DateOfReading, :read.KindOfMeter, :read.MeterCount, :read.MeterId, :read.Substitute)
			WHERE id = :rid
			""")
	void update(@Bind("rid") int id, @BindBean("cus") DReading o);

	@Override
	@SqlUpdate("""
			UPDATE reading
			SET (comment, customer_id, date_of_reading, kind_of_meter, meter_count, meter_id, substitute) 
			Values(:read.Comment, :read.Customer.Id, :read.DateOfReading, :read.KindOfMeter, :read.MeterCount, :read.MeterId, :read.Substitute)
			WHERE id = :read.id
			""")
	void update(@BindBean("read") DReading o);

}
