package dev.hv.rest;

import java.net.URI;

import org.glassfish.jersey.jdkhttp.JdkHttpServerFactory;
import org.glassfish.jersey.server.ResourceConfig;

import com.sun.net.httpserver.HttpServer;

public class Server {
	
	public static void main (String[] args) {
		final String pack = "dev.hv.rest.resources";
		String url = "http://localhost:8080/rest";
		System.out.println("Start server");
		System.out.println(url);
		final ResourceConfig rc = new ResourceConfig().packages(pack);
		final HttpServer server = JdkHttpServerFactory.createHttpServer(
		URI.create(url), rc);
		System.out.println("Ready for Requests....");
	}
}
