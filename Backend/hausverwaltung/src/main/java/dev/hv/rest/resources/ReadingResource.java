package dev.hv.rest.resources;

import dev.hv.rest.model.IRCustomer;
import dev.hv.rest.model.IRReading;
import dev.hv.rest.model.IRUser;
import dev.hv.rest.model.RCustomer;
import dev.hv.rest.model.RReading;
import dev.hv.rest.model.RUser;
import dev.hv.rest.util.CustomerJsonUtil;
import dev.hv.rest.util.ReadingsJsonUtil;
import dev.hv.rest.util.UserJsonUtil;

import java.util.List;

import org.jdbi.v3.core.statement.UnableToExecuteStatementException;

import jakarta.ws.rs.Consumes;
import jakarta.ws.rs.DELETE;
import jakarta.ws.rs.FormParam;
import jakarta.ws.rs.GET;
import jakarta.ws.rs.POST;
import jakarta.ws.rs.Path;
import jakarta.ws.rs.PathParam;
import jakarta.ws.rs.Produces;
import jakarta.ws.rs.core.MediaType;
import jakarta.ws.rs.core.Response;

@Path("reading")
public class ReadingResource {

	@GET
	@Path("get/{id}")
	@Produces(MediaType.APPLICATION_JSON)
	public Response getUserById(@PathParam("id") int id) {
		try {
			IRReading reading = new ReadingsJsonUtil().getWithID(id);
			return Response.status(Response.Status.OK).entity(reading).build();
		} catch (NullPointerException eNullPointerException) {
			return Response.status(Response.Status.NO_CONTENT).entity("There is no reading with id "+id+".").build();
		}
	}

	@GET
	@Path("get")
	@Produces(MediaType.APPLICATION_JSON)
	public Response getAllUsers() {
		List<IRReading> reading = (new ReadingsJsonUtil()).getAll();
		return Response.status(Response.Status.OK).entity(reading).build();
	}
	
	@POST
	@Path("create")
	@Consumes(MediaType.APPLICATION_JSON)
	@Produces(MediaType.APPLICATION_JSON)
	public Response createReading(RReading reading) {
			try {
				ReadingsJsonUtil util = new ReadingsJsonUtil();
				System.out.println(reading.getId());
				int created = util.insert(reading);
				if (created > 0) {
					IRReading read = util.getWithID(reading.getId());
					
					return Response.status(Response.Status.CREATED).entity(read).build();
				}
				return Response.status(Response.Status.INTERNAL_SERVER_ERROR)
						.entity("There was an Error inserting the reading into the database!").build();
			} catch (UnableToExecuteStatementException sqlException) {
				if (sqlException.getMessage().contains("[SQLITE_CONSTRAINT_PRIMARYKEY] A PRIMARY KEY constraint failed")) {
					return Response.status(Response.Status.CONFLICT)
							.entity("There is already a reading with the id " + reading.getId() + "!").build();
				} else
					throw sqlException;
			} catch (Exception ex) {
				return Response.status(Response.Status.INTERNAL_SERVER_ERROR)
						.entity("There was an error while creating the new reading. Details: " + ex.getMessage()).build();
			}
		}
	
	@DELETE
	@Path("delete/{id}")
	@Produces(MediaType.APPLICATION_JSON)
	public Response deleteReading(@PathParam("id") int id) {
		ReadingsJsonUtil util = new ReadingsJsonUtil();
		util.delete(id);
		return Response.status(Response.Status.OK).build();
	}
	
	
	@POST
	@Path("update/{id}")
	@Consumes(MediaType.APPLICATION_JSON)
	@Produces(MediaType.APPLICATION_JSON)
	public Response updateUser(@PathParam("id") int id, RReading reading) {
		ReadingsJsonUtil util = new ReadingsJsonUtil();
		CustomerJsonUtil cus_util = new CustomerJsonUtil();
		
		try {
			if (reading == null) {
				return Response.status(Response.Status.BAD_REQUEST)
						.entity("There was no Reading given!").build();
			}
			IRReading dbReading = util.getWithID(id);
			if (reading.getComment() != null)
				dbReading.setComment(reading.getComment());
			if (reading.getDateofreading() != null)
				dbReading.setDateofreading(reading.getDateofreading());
			if (reading.getId() != null)
				dbReading.setId(reading.getId());
			if (reading.getKindofmeter() != null)
				dbReading.setKindofmeter(reading.getKindofmeter());
			if (reading.getMetercount() != null)
				dbReading.setMetercount(reading.getMetercount());
			if (reading.getMeterid() != null)
				dbReading.setMeterid(reading.getMeterid());
			if (reading.getSubstitute() != null)
				dbReading.setSubstitute(reading.getSubstitute());
			
			if (reading.getCustomer() != null)
				dbReading.setCustomer(cus_util.getWithID(reading.getCustomer().getId()));
			
			util.update(dbReading);
			return Response.status(Response.Status.OK).entity(dbReading).build();
		} catch (NullPointerException eNullPointerException) {
			return Response.status(Response.Status.NO_CONTENT)
					.entity("There is no reading with the id " + id + " to update!").build();
		} catch (Exception ex) {
			return Response.status(Response.Status.INTERNAL_SERVER_ERROR)
					.entity("There was an error while updating the new Reading. Details: " + ex.getMessage()).build();
		}
	}

}

