package dev.hv.rest;

import java.net.URI;

import org.glassfish.jersey.jdkhttp.JdkHttpServerFactory;
import org.glassfish.jersey.server.ResourceConfig;

import com.sun.net.httpserver.HttpServer;

import jakarta.ws.rs.ProcessingException;

public class Server {
	
	public static void main (String[] args) {
		final String pack = "dev.hv.rest.resources";
		String url = "http://localhost:8080/rest";
		final ResourceConfig rc = new ResourceConfig().packages(pack);
		try {
			final HttpServer server = JdkHttpServerFactory.createHttpServer(URI.create(url), rc);
			System.out.println("The server is now listening on "+server.getAddress().getHostName()+":"+server.getAddress().getPort());
		} catch (ProcessingException e) {
			e.printStackTrace();
		}
	}
}
